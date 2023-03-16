<?php defined('ALTUMCODE') || die() ?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <nav aria-label="breadcrumb">
        <ol class="custom-breadcrumbs small">
            <li>
                <a href="<?= url('images') ?>"><?= l('images.breadcrumb') ?></a><i class="fa fa-fw fa-angle-right"></i>
            </li>
            <li class="active" aria-current="page"><?= l('image_update.breadcrumb') ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 text-truncate mb-0"><?= sprintf(l('image_update.header'), $data->image->name) ?></h1>

        <div class="d-flex align-items-center col-auto p-0">
            <?= include_view(THEME_PATH . 'views/images/image_dropdown_button.php', ['id' => $data->image->image_id, 'resource_name' => $data->image->name]) ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">

            <form action="" method="post" role="form">
                <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />

                <div class="form-group">
                    <label for="name"><i class="fa fa-fw fa-signature fa-sm text-muted mr-1"></i> <?= l('global.name') ?></label>
                    <input type="text" id="name" name="name" class="form-control" value="<?= $data->image->name ?>" required="required" />
                </div>

                <div class="form-group">
                    <label for="input"><i class="fa fa-fw fa-paragraph fa-sm text-muted mr-1"></i> <?= l('images.input') ?></label>
                    <textarea id="input" name="input" class="form-control" readonly="readonly"><?= $data->image->input ?></textarea>
                </div>

                <div class="form-group">
                    <label for="input"><i class="fa fa-fw fa-image fa-sm text-muted mr-1"></i> <?= l('images.image') ?></label>
                    <div>
                        <img src="<?= UPLOADS_FULL_URL . 'images/' . $data->image->image ?>" class="img-fluid" alt="<?= $data->image->input ?>" />
                    </div>
                </div>

                <div class="form-group">
                    <label for="size"><i class="fa fa-fw fa-expand-arrows-alt fa-sm text-muted mr-1"></i> <?= l('images.size') ?></label>
                    <input type="text" id="size" name="size" class="form-control" value="<?= $data->image->size ?>" readonly="readonly" />
                </div>

                <div class="form-group">
                    <label for="variants"><i class="fa fa-fw fa-list-ol fa-sm text-muted mr-1"></i> <?= l('images.variants') ?></label>
                    <input type="text" id="variants" name="variants" class="form-control" value="<?= $data->image->settings->variants ?>" readonly="readonly" />
                </div>

                <div class="form-group">
                    <div class="d-flex flex-column flex-xl-row justify-content-between">
                        <label for="project_id"><i class="fa fa-fw fa-sm fa-project-diagram text-muted mr-1"></i> <?= l('projects.project_id') ?></label>
                        <a href="<?= url('project-create') ?>" target="_blank" class="small mb-2"><i class="fa fa-fw fa-sm fa-plus mr-1"></i> <?= l('projects.create') ?></a>
                    </div>
                    <select id="project_id" name="project_id" class="form-control">
                        <option value=""><?= l('global.none') ?></option>
                        <?php foreach($data->projects as $project_id => $project): ?>
                            <option value="<?= $project_id ?>" <?= $data->image->project_id == $project_id ? 'selected="selected"' : null ?>><?= $project->name ?></option>
                        <?php endforeach ?>
                    </select>
                    <small class="form-text text-muted"><?= l('projects.project_id_help') ?></small>
                </div>

                <button type="submit" name="submit" class="btn btn-block btn-primary"><?= l('global.update') ?></button>
            </form>

        </div>
    </div>
</div>

<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/universal_delete_modal_form.php', [
    'name' => 'image',
    'resource_id' => 'image_id',
    'has_dynamic_resource_name' => true,
    'path' => 'images/delete'
]), 'modals'); ?>

<?php include_view(THEME_PATH . 'views/partials/clipboard_js.php') ?>

<?php include_view(THEME_PATH . 'views/partials/color_picker_js.php') ?>
