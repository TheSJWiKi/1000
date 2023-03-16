<?php
/*
 * @copyright Copyright (c) 2021 AltumCode (https://altumcode.com/)
 *
 * This software is exclusively sold through https://altumcode.com/ by the AltumCode author.
 * Downloading this product from any other sources and running it without a proper license is illegal,
 *  except the official ones linked from https://altumcode.com/.
 */

namespace Altum\Controllers;

use Altum\Alerts;
use Altum\Response;
use Altum\Uploads;

class ImageCreate extends Controller {

    public function index() {
        \Altum\Authentication::guard();

        if(!\Altum\Plugin::is_active('aix') || !settings()->aix->images_is_enabled) {
            redirect('dashboard');
        }

        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('create.images')) {
            Alerts::add_info(l('global.info_message.team_no_access'));
            redirect('images');
        }

        /* Check for the plan limit */
        $images_current_month = db()->where('user_id', $this->user->user_id)->getValue('users', '`aix_images_current_month`');
        if($this->user->plan_settings->images_per_month_limit != -1 && $images_current_month >= $this->user->plan_settings->images_per_month_limit) {
            Alerts::add_info(l('global.info_message.plan_feature_limit'));
            redirect('images');
        }

        /* Get available projects */
        $projects = (new \Altum\Models\Projects())->get_projects_by_user_id($this->user->user_id);

        $values = [
            'name' => $_POST['name'] ?? '',
            'input' => $_POST['input'] ?? '',
            'size' => $_POST['size'] ?? '256x256',
            'variants' => $_POST['variants'] ?? 1,
            'project_id' => $_POST['project_id'] ?? null,
        ];

        /* Prepare the View */
        $data = [
            'values' => $values,
            'projects' => $projects ?? [],
        ];

        $view = new \Altum\View(THEME_PATH . 'views/image-create/index', (array) $this, true);

        $this->add_view_content('content', $view->run($data));

    }

    public function create_ajax() {
        //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Response::json('Please create an account on the demo to test out this function.', 'error');

        if(empty($_POST)) {
            redirect();
        }

        \Altum\Authentication::guard();

        if(!\Altum\Plugin::is_active('aix') || !settings()->aix->images_is_enabled) {
            redirect('dashboard');
        }

        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('create.images')) {
            Response::json(l('global.info_message.team_no_access'), 'error');
        }

        /* Check for the plan limit */
        $images_current_month = db()->where('user_id', $this->user->user_id)->getValue('users', '`aix_images_current_month`');
        if($this->user->plan_settings->images_per_month_limit != -1 && $images_current_month >= $this->user->plan_settings->images_per_month_limit) {
            Response::json(l('global.info_message.plan_feature_limit'), 'error');
        }

        /* Get available projects */
        $projects = (new \Altum\Models\Projects())->get_projects_by_user_id($this->user->user_id);

        $_POST['name'] = input_clean($_POST['name'], 64);
        $_POST['input'] = input_clean($_POST['input'], 1000);
        $_POST['size'] = $_POST['size'] && in_array($_POST['size'], ['256x256', '512x512', '1024x1024']) ? $_POST['size'] : '256x256';
        $_POST['variants'] = (int) $_POST['variants'] < 0 || (int) $_POST['variants'] > 3 ? 1 : (int) $_POST['variants'];
        $_POST['project_id'] = !empty($_POST['project_id']) && array_key_exists($_POST['project_id'], $projects) ? (int) $_POST['project_id'] : null;

        /* Check for any errors */
        $required_fields = ['name', 'input'];
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || (isset($_POST[$field]) && empty($_POST[$field]) && $_POST[$field] != '0')) {
                Response::json(l('global.error_message.empty_fields'), 'error');
            }
        }

        if(!\Altum\Csrf::check('global_token')) {
            Response::json(l('global.error_message.invalid_csrf_token'), 'error');
        }

        /* Check for timeouts */
        if(settings()->aix->input_moderation_is_enabled) {
            $cache_instance = \Altum\Cache::$adapter->getItem('user?flagged=' . $this->user->user_id);
            if(!is_null($cache_instance->get())) {
                Response::json(l('documents.error_message.timed_out'), 'error');
            }
        }

        /* Check for moderation */
        if(settings()->aix->input_moderation_is_enabled) {
            try {
                $response = \Unirest\Request::post(
                    'https://api.openai.com/v1/moderations',
                    [
                        'Authorization' => 'Bearer ' . settings()->aix->openai_api_key,
                        'Content-Type' => 'application/json',
                    ],
                    \Unirest\Request\Body::json([
                        'input' => $_POST['input'],
                    ])
                );

                if($response->code >= 400) {
                    Response::json($response->body->error->message, 'error');
                }

                if($response->body->results[0]->flagged ?? null) {
                    /* Time out the user for a few minutes */
                    \Altum\Cache::$adapter->save(
                        $cache_instance->set('true')->expiresAfter(3 * 60)->addTag('users')->addTag('user_id=' . $this->user->user_id)
                    );

                    /* Return the error */
                    Response::json(l('documents.error_message.flagged'), 'error');
                }

            } catch (\Exception $exception) {
                Response::json($exception->getMessage(), 'error');
            }
        }

        try {
            $response = \Unirest\Request::post(
                'https://api.openai.com/v1/images/generations',
                [
                    'Authorization' => 'Bearer ' . settings()->aix->openai_api_key,
                    'Content-Type' => 'application/json',
                ],
                \Unirest\Request\Body::json([
                    'prompt' => $_POST['input'],
                    'size' => $_POST['size'],
                    'n' => $_POST['variants'],
                    'response_format' => 'b64_json'
                ])
            );

            if($response->code >= 400) {
                Response::json($response->body->error->message, 'error');
            }

        } catch (\Exception $exception) {
            Response::json($exception->getMessage(), 'error');
        }

        /* Go through each result */
        foreach($response->body->data as $result) {
            /* Save the image temporarily */
            $temp_image_name = md5(uniqid()) . '.png';
            file_put_contents(Uploads::get_full_path('images') . $temp_image_name , base64_decode($result->b64_json));

            /* Fake uploaded image */
            $_FILES['image'] = [
                'name' => 'altum.png',
                'tmp_name' => Uploads::get_full_path('images') . $temp_image_name,
                'error' => null,
                'size' => 0,
            ];

            $image = \Altum\Uploads::process_upload_fake('images', 'image', 'json_error', null);

            $settings = json_encode([
                'variants' => $_POST['variants'],
            ]);

            /* Prepare the statement and execute query */
            $image_id = db()->insert('images', [
                'user_id' => $this->user->user_id,
                'name' => $_POST['name'],
                'input' => $_POST['input'],
                'image' => $image,
                'size' => $_POST['size'],
                'settings' => $settings,
                'datetime' => \Altum\Date::$date,
            ]);
        }

        /* Prepare the statement and execute query */
        db()->where('user_id', $this->user->user_id)->update('users', [
            'aix_images_current_month' => db()->inc($_POST['variants'])
        ]);

        /* Set a nice success message */
        Response::json(sprintf(l('global.success_message.create1'), '<strong>' . $_POST['name'] . '</strong>'), 'success', ['url' => url('image-update/' . $image_id)]);

    }

}
