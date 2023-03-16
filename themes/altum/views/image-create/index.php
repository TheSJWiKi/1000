<?php defined('ALTUMCODE') || die() ?>


<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <nav aria-label="breadcrumb">
        <ol class="custom-breadcrumbs small">
            <li>
                <a href="<?= url('images') ?>"><?= l('images.breadcrumb') ?></a><i class="fa fa-fw fa-angle-right"></i>
            </li>
            <li class="active" aria-current="page"><?= l('image_create.breadcrumb') ?></li>
        </ol>
    </nav>

    <h1 class="h4 text-truncate mb-4"><?= l('image_create.header') ?></h1>

    <div class="card">
        <div class="card-body">

            <form id="image_create" action="" method="post" role="form">
                <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />

                <div class="notification-container"></div>

                <div class="form-group">
                    <label for="name"><i class="fa fa-fw fa-signature fa-sm text-muted mr-1"></i> <?= l('global.name') ?></label>
                    <input type="text" id="name" name="name" class="form-control" value="<?= $data->values['name'] ?>" required="required" />
                </div>

                <div class="form-group">
                    <label for="input"><i class="fa fa-fw fa-paragraph fa-sm text-muted mr-1"></i> <?= l('images.input') ?></label>
                    <textarea id="input" name="input" class="form-control" maxlength="1000" required="required"><?= $data->values['input'] ?? null ?></textarea>
                </div>

                <div class="form-group">
                    <label for="size"><i class="fa fa-fw fa-expand-arrows-alt fa-sm text-muted mr-1"></i> <?= l('images.size') ?></label>
                    <div class="row btn-group-toggle" data-toggle="buttons">
                        <?php foreach(['256x256', '512x512', '1024x1024'] as $key): ?>
                            <div class="col-4">
                                <label class="btn btn-light btn-block">
                                    <input type="radio" name="size" value="<?= $key ?>" class="custom-control-input" <?= $data->values['size'] == $key ? 'checked="checked"' : null ?> />
                                    <?= $key ?>
                                </label>
                            </div>
                        <?php endforeach ?>
                    </div>
                    <small class="form-text text-muted"><?= l('images.size_help') ?></small>
                </div>

                <div class="form-group">
                    <label for="variants"><i class="fa fa-fw fa-list-ol fa-sm text-muted mr-1"></i> <?= l('images.variants') ?></label>
                    <div class="row btn-group-toggle" data-toggle="buttons">
                        <?php foreach([1,2,3] as $key): ?>
                            <div class="col-12 col-lg-4">
                                <label class="btn btn-light btn-block">
                                    <input type="radio" name="variants" value="<?= $key ?>" class="custom-control-input" <?= $data->values['variants'] == $key ? 'checked="checked"' : null ?> />
                                    <?= sprintf(l('images.x_variants'), $key) ?>
                                </label>
                            </div>
                        <?php endforeach ?>
                    </div>
                    <small class="form-text text-muted"><?= l('images.variants_help') ?></small>
                </div>

                <div class="form-group">
                    <div class="d-flex flex-column flex-xl-row justify-content-between">
                        <label for="project_id"><i class="fa fa-fw fa-sm fa-project-diagram text-muted mr-1"></i> <?= l('projects.project_id') ?></label>
                        <a href="<?= url('project-create') ?>" target="_blank" class="small mb-2"><i class="fa fa-fw fa-sm fa-plus mr-1"></i> <?= l('projects.create') ?></a>
                    </div>
                    <select id="project_id" name="project_id" class="form-control">
                        <option value=""><?= l('global.none') ?></option>
                        <?php foreach($data->projects as $project_id => $project): ?>
                            <option value="<?= $project_id ?>" <?= $data->values['project_id'] == $project_id ? 'selected="selected"' : null ?>><?= $project->name ?></option>
                        <?php endforeach ?>
                    </select>
                    <small class="form-text text-muted"><?= l('projects.project_id_help') ?></small>
                </div>

                <button type="submit" name="submit" class="btn btn-block btn-primary" data-is-ajax><?= l('global.create') ?></button>
            </form>

        </div>
    </div>
</div>

<?php ob_start() ?>
<script>
    /* Form submission */
    document.querySelector('#image_create').addEventListener('submit', async event => {
        event.preventDefault();

        pause_submit_button(document.querySelector('#image_create').querySelector('[type="submit"][name="submit"]'));

        /* Notification container */
        let notification_container = event.currentTarget.querySelector('.notification-container');
        notification_container.innerHTML = '';

        /* Prepare form data */
        let form = new FormData(document.querySelector('#image_create'));

        /* Send request to server */
        let response = await fetch(`${url}image-create/create_ajax`, {
            method: 'post',
            body: form
        });

        let data = null;
        try {
            data = await response.json();
        } catch (error) { /* :) */ }

        if(!response.ok) {
            enable_submit_button(document.querySelector('#image_create').querySelector('[type="submit"][name="submit"]'));
            display_notifications(<?= json_encode(l('global.error_message.basic')) ?>, 'error', notification_container);
            notification_container.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        if (data.status == 'error') {
            enable_submit_button(document.querySelector('#image_create').querySelector('[type="submit"][name="submit"]'));
            display_notifications(data.message, 'error', notification_container);
            notification_container.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else if (data.status == 'success') {
            /* Redirect */
            redirect(data.details.url, true);
        }
    });
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

