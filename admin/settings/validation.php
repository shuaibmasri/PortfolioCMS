<?php
declare(strict_types=1);

if (!function_exists('websiteSettingsDefinitions')) {
    /**
     * Returns the canonical Website Settings field definition map.
     *
     * @return array<string, array<string, mixed>>
     */
    function websiteSettingsDefinitions(): array
    {
        return [
            'website_name' => ['group' => 'general', 'label' => 'Website Name', 'type' => 'text', 'required' => true, 'max_length' => 150],
            'website_tagline' => ['group' => 'general', 'label' => 'Website Tagline', 'type' => 'text', 'required' => false, 'max_length' => 255],
            'website_description' => ['group' => 'general', 'label' => 'Website Description', 'type' => 'textarea', 'required' => false, 'max_length' => 2000, 'rows' => 4],
            'footer_text' => ['group' => 'general', 'label' => 'Footer Text', 'type' => 'text', 'required' => false, 'max_length' => 255],
            'copyright_text' => ['group' => 'general', 'label' => 'Copyright Text', 'type' => 'text', 'required' => false, 'max_length' => 255],

            'contact_email' => ['group' => 'contact', 'label' => 'Contact Email', 'type' => 'email', 'required' => true, 'max_length' => 255],
            'contact_phone' => ['group' => 'contact', 'label' => 'Contact Phone', 'type' => 'text', 'required' => false, 'max_length' => 50],
            'address' => ['group' => 'contact', 'label' => 'Address', 'type' => 'textarea', 'required' => false, 'max_length' => 500, 'rows' => 3],
            'google_maps_url' => ['group' => 'contact', 'label' => 'Google Maps URL', 'type' => 'url', 'required' => false, 'max_length' => 500],

            'default_language' => ['group' => 'localization', 'label' => 'Default Language', 'type' => 'text', 'required' => true, 'max_length' => 10, 'pattern' => '/^[A-Za-z0-9_-]{2,10}$/'],
            'time_zone' => ['group' => 'localization', 'label' => 'Time Zone', 'type' => 'select', 'required' => true, 'max_length' => 64, 'options' => []],
            'date_format' => ['group' => 'localization', 'label' => 'Date Format', 'type' => 'select', 'required' => true, 'max_length' => 32, 'options' => ['Y-m-d' => '2026-07-15', 'd/m/Y' => '15/07/2026', 'm/d/Y' => '07/15/2026', 'd M Y' => '15 Jul 2026', 'M d, Y' => 'Jul 15, 2026']],
            'time_format' => ['group' => 'localization', 'label' => 'Time Format', 'type' => 'select', 'required' => true, 'max_length' => 32, 'options' => ['H:i' => '14:30', 'h:i A' => '02:30 PM']],

            'meta_title' => ['group' => 'seo', 'label' => 'Meta Title', 'type' => 'text', 'required' => true, 'max_length' => 255],
            'meta_description' => ['group' => 'seo', 'label' => 'Meta Description', 'type' => 'textarea', 'required' => false, 'max_length' => 500, 'rows' => 3],
            'meta_keywords' => ['group' => 'seo', 'label' => 'Meta Keywords', 'type' => 'text', 'required' => false, 'max_length' => 500],
            'robots' => ['group' => 'seo', 'label' => 'Robots', 'type' => 'select', 'required' => true, 'max_length' => 32, 'options' => ['index,follow' => 'index,follow', 'index,nofollow' => 'index,nofollow', 'noindex,follow' => 'noindex,follow', 'noindex,nofollow' => 'noindex,nofollow']],
            'canonical_url' => ['group' => 'seo', 'label' => 'Canonical URL', 'type' => 'url', 'required' => true, 'max_length' => 500],

            'maintenance_mode' => ['group' => 'system', 'label' => 'Maintenance Mode', 'type' => 'boolean', 'required' => false, 'max_length' => 1],
            'maintenance_message' => ['group' => 'system', 'label' => 'Maintenance Message', 'type' => 'textarea', 'required' => false, 'max_length' => 500, 'rows' => 3],

            'facebook_url' => ['group' => 'social', 'label' => 'Facebook', 'type' => 'url', 'required' => false, 'max_length' => 500],
            'linkedin_url' => ['group' => 'social', 'label' => 'LinkedIn', 'type' => 'url', 'required' => false, 'max_length' => 500],
            'github_url' => ['group' => 'social', 'label' => 'GitHub', 'type' => 'url', 'required' => false, 'max_length' => 500],
            'x_url' => ['group' => 'social', 'label' => 'X', 'type' => 'url', 'required' => false, 'max_length' => 500],
            'youtube_url' => ['group' => 'social', 'label' => 'YouTube', 'type' => 'url', 'required' => false, 'max_length' => 500],
        ];
    }

    /**
     * Returns grouped sections for the settings UI.
     *
     * @return array<string, string>
     */
    function websiteSettingsGroups(): array
    {
        return [
            'general' => 'General',
            'contact' => 'Contact',
            'localization' => 'Localization',
            'seo' => 'SEO',
            'system' => 'System',
            'social' => 'Social',
        ];
    }

    /**
     * Returns the default value for each settings key.
     *
     * @return array<string, string|int>
     */
    function websiteSettingsDefaults(): array
    {
        $defaults = [];
        foreach (websiteSettingsDefinitions() as $key => $definition) {
            $defaults[$key] = $definition['type'] === 'boolean' ? 0 : '';
        }

        return $defaults;
    }

    /**
     * Returns the valid time zones available to the application.
     *
     * @return list<string>
     */
    function websiteSettingsTimeZones(): array
    {
        return \DateTimeZone::listIdentifiers();
    }

    /**
     * Normalizes the current database rows into a flat key/value map.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, string|int>
     */
    function websiteSettingsFlattenRows(array $rows): array
    {
        $values = websiteSettingsDefaults();

        foreach ($rows as $row) {
            $key = (string) ($row['setting_key'] ?? '');
            if ($key === '' || !array_key_exists($key, $values)) {
                continue;
            }

            $definition = websiteSettingsDefinitions()[$key];
            $value = $row['setting_value'] ?? '';
            if (($definition['type'] ?? '') === 'boolean') {
                $values[$key] = (int) ((string) $value === '1');
                continue;
            }

            $values[$key] = is_scalar($value) ? (string) $value : '';
        }

        return $values;
    }

    /**
     * Reads the current settings from the database.
     *
     * @return array<string, string|int>
     */
    function websiteSettingsLoad(PDO $pdo): array
    {
        try {
            $statement = $pdo->query('SELECT setting_key, setting_value FROM website_settings');
            $rows = $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];

            return websiteSettingsFlattenRows(is_array($rows) ? $rows : []);
        } catch (Throwable $exception) {
            error_log($exception->getMessage());

            return websiteSettingsDefaults();
        }
    }

    /**
     * Returns the value that should be shown in a form field.
     *
     * @param array<string, string|int> $values
     */
    function websiteSettingsValue(array $values, string $key): string
    {
        $value = $values[$key] ?? '';

        return is_int($value) ? (string) $value : (string) $value;
    }

    /**
     * Normalizes and validates a submitted settings payload.
     *
     * @param array<string, mixed> $input
     * @return array{data: array<string, string|int>, errors: array<string, string>}
     */
    function websiteSettingsValidate(array $input): array
    {
        $definitions = websiteSettingsDefinitions();
        $data = websiteSettingsDefaults();
        $errors = [];

        foreach ($definitions as $key => $definition) {
            $type = (string) ($definition['type'] ?? 'text');
            $maxLength = (int) ($definition['max_length'] ?? 0);
            $rawValue = $input[$key] ?? '';

            if ($type === 'boolean') {
                $data[$key] = !empty($rawValue) ? 1 : 0;
                continue;
            }

            $value = trim((string) $rawValue);
            $valueLength = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
            if ($maxLength > 0 && $valueLength > $maxLength) {
                $errors[$key] = $definition['label'] . ' is too long.';
                $value = function_exists('mb_substr') ? mb_substr($value, 0, $maxLength, 'UTF-8') : substr($value, 0, $maxLength);
            }

            if (($definition['required'] ?? false) === true && $value === '') {
                $errors[$key] = $definition['label'] . ' is required.';
            }

            if ($value !== '') {
                switch ($type) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$key] = 'Please enter a valid email address.';
                        }
                        break;

                    case 'url':
                        if (!filter_var($value, FILTER_VALIDATE_URL)) {
                            $errors[$key] = 'Please enter a valid URL.';
                        }
                        break;

                    case 'select':
                        $options = array_keys((array) ($definition['options'] ?? []));
                        if ($options !== [] && !in_array($value, $options, true)) {
                            $errors[$key] = 'Please select a valid option.';
                        }
                        break;
                }
            }

            if ($key === 'default_language' && $value !== '' && preg_match('/^[A-Za-z0-9_-]{2,10}$/', $value) !== 1) {
                $errors[$key] = 'Please enter a valid language code.';
            }

            if ($key === 'time_zone' && $value !== '' && !in_array($value, websiteSettingsTimeZones(), true)) {
                $errors[$key] = 'Please select a valid time zone.';
            }

            $data[$key] = $value;
        }

        return ['data' => $data, 'errors' => $errors];
    }
}
