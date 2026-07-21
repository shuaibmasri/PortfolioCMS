<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once __DIR__ . '/validation.php';
requireAdmin();

$pageTitle = 'Website Settings';
$activeMenu = 'website-settings';
$breadcrumbs = [['label' => 'Dashboard'], ['label' => 'Website Settings']];

$definitions = websiteSettingsDefinitions();
$groups = websiteSettingsGroups();
$values = websiteSettingsLoad($pdo);
$timeZones = websiteSettingsTimeZones();

$renderField = static function (string $key, array $definition, array $values) use ($timeZones): void {
    $type = (string) ($definition['type'] ?? 'text');
    $label = (string) ($definition['label'] ?? $key);
    $required = (bool) ($definition['required'] ?? false);
    $maxLength = (int) ($definition['max_length'] ?? 0);
    $rows = (int) ($definition['rows'] ?? 3);
    $value = websiteSettingsValue($values, $key);
    $options = (array) ($definition['options'] ?? []);
    $fieldId = $key;
    ?>
    <div class="col-md-6">
        <label class="form-label" for="<?= escape($fieldId) ?>"><?= escape($label) ?><?= $required ? ' *' : '' ?></label>
        <?php if ($type === 'textarea'): ?>
            <textarea
                class="form-control"
                id="<?= escape($fieldId) ?>"
                name="<?= escape($key) ?>"
                rows="<?= (int) $rows ?>"
                maxlength="<?= (int) $maxLength ?>"
                <?= $required ? 'required' : '' ?>
            ><?= escape($value) ?></textarea>
        <?php elseif ($type === 'select'): ?>
            <select
                class="form-select"
                id="<?= escape($fieldId) ?>"
                name="<?= escape($key) ?>"
                <?= $required ? 'required' : '' ?>
            >
                <option value="">Select <?= escape($label) ?></option>
                <?php if ($key === 'time_zone'): ?>
                    <?php foreach ($timeZones as $timeZone): ?>
                        <option value="<?= escape($timeZone) ?>"<?= $value === $timeZone ? ' selected' : '' ?>><?= escape($timeZone) ?></option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php foreach ($options as $optionValue => $optionLabel): ?>
                        <option value="<?= escape((string) $optionValue) ?>"<?= $value === (string) $optionValue ? ' selected' : '' ?>><?= escape((string) $optionLabel) ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        <?php elseif ($type === 'boolean'): ?>
            <div class="form-check form-switch pt-2">
                <input class="form-check-input" type="checkbox" role="switch" id="<?= escape($fieldId) ?>" name="<?= escape($key) ?>" value="1"<?= $value === '1' ? ' checked' : '' ?>>
                <label class="form-check-label" for="<?= escape($fieldId) ?>"><?= escape($label) ?></label>
            </div>
        <?php else: ?>
            <input
                class="form-control"
                id="<?= escape($fieldId) ?>"
                name="<?= escape($key) ?>"
                type="<?= $type === 'url' ? 'url' : ($type === 'email' ? 'email' : 'text') ?>"
                maxlength="<?= (int) $maxLength ?>"
                value="<?= escape($value) ?>"
                <?= $required ? 'required' : '' ?>
                <?= $key === 'canonical_url' ? 'placeholder="https://example.com"' : '' ?>
                <?= $key === 'default_language' ? 'pattern="[A-Za-z0-9_-]{2,10}" title="Use 2 to 10 letters, numbers, underscores, or hyphens."' : '' ?>
            >
        <?php endif; ?>
        <div class="form-text">
            <?php if ($key === 'website_name'): ?>This name appears in the admin UI and public pages.
            <?php elseif ($key === 'canonical_url'): ?>Use your public site URL, for example `https://example.com`.
            <?php elseif ($key === 'time_zone'): ?>Choose the time zone used for dates and times.
            <?php elseif ($key === 'maintenance_mode'): ?>When enabled, visitors can be shown a maintenance notice.
            <?php else: ?><?= escape($label) ?><?= $required ? ' is required.' : '.' ?>
            <?php endif; ?>
        </div>
        <div class="invalid-feedback"><?= escape($label) ?> is invalid.</div>
    </div>
    <?php
};

ob_start();
?>
<form id="settingsForm" class="settings-form" data-load-url="<?= escape(url('admin/settings/ajax.php')) ?>" data-save-url="<?= escape(url('admin/settings/save.php')) ?>" novalidate>
    <input type="hidden" name="<?= escape(CSRF_TOKEN_NAME) ?>" value="<?= escape(csrfToken()) ?>">
    <div id="settingsAlert" class="mb-3" aria-live="polite" aria-atomic="true"></div>

    <section class="dashboard-panel mb-4">
        <div class="dashboard-panel__header flex-wrap gap-3">
            <div>
                <h2>Website Settings</h2>
                <p>Manage the content, SEO, localization, and system options for your portfolio site.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-outline-secondary" type="button" id="settingsReloadBtn">
                    <i class="fa fa-refresh me-1"></i>Reload
                </button>
                <button class="btn btn-primary" type="submit" id="settingsSaveBtn">
                    <span class="settings-save-text"><i class="fa fa-save me-1"></i>Save Changes</span>
                    <span class="settings-save-spinner spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </div>
        <div class="p-4 pt-2">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="p-3 rounded-4 border bg-light h-100">
                        <div class="fw-semibold text-uppercase small text-muted">General</div>
                        <div class="h5 mb-1">Branding and footer text</div>
                        <p class="mb-0 text-muted small">Define the public identity shown across the site.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded-4 border bg-light h-100">
                        <div class="fw-semibold text-uppercase small text-muted">SEO</div>
                        <div class="h5 mb-1">Metadata and canonical URL</div>
                        <p class="mb-0 text-muted small">Keep search engines aligned with the current site structure.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded-4 border bg-light h-100">
                        <div class="fw-semibold text-uppercase small text-muted">System</div>
                        <div class="h5 mb-1">Maintenance controls</div>
                        <p class="mb-0 text-muted small">Show a safe maintenance message when the site is offline.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php foreach ($groups as $groupKey => $groupLabel): ?>
        <?php
        $groupDefinitions = array_filter(
            $definitions,
            static fn(array $definition): bool => ($definition['group'] ?? '') === $groupKey
        );
        ?>
        <section class="dashboard-panel mb-4">
            <div class="dashboard-panel__header">
                <div>
                    <h2><?= escape($groupLabel) ?></h2>
                    <p><?= escape($groupLabel) ?> configuration.</p>
                </div>
            </div>
            <div class="p-4 pt-2">
                <div class="row g-3">
                    <?php foreach ($groupDefinitions as $key => $definition): ?>
                        <?php $renderField($key, $definition, $values); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endforeach; ?>
</form>
<?php
$pageContent = (string) ob_get_clean();
$pageScripts = '<script src="' . escape(asset('js/settings.js')) . '"></script>';

require dirname(__DIR__, 2) . '/includes/admin-layout.php';
