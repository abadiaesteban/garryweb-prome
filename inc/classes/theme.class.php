<?php

class theme
{
    /**
     * @return array|bool|string|null
     */
    public static function current()
    {
        return getSetting('theme', 'value');
    }

    /**
     * @param bool $default
     * @param bool $settings
     * @return string
     */
    public static function options($default = true, $settings = false)
    {
        $current = theme::current();

        if (!$settings && getSetting('disable_theme_selector', 'value2') == 0) {
            if (isset($_COOKIE['prometheus_theme'])) {
                $current = $_COOKIE['prometheus_theme'];
            }
        }

        $dirs = scandir('themes');
        unset($dirs[0]);
        unset($dirs[1]);

        $ret = '';
        if ($default) {
            $ret = '<option value="">Default</option>';
        }

        $active = '';

        foreach ($dirs as $themes) {
            if ($themes == $current && $default) {
                $active = 'selected';
            }

            $ret .= '<option value="' . $themes . '" ' . $active . '>' . $themes . '</option>';
            $active = '';
        }

        return $ret;
    }

    /**
     * @param $theme
     */
    public static function del($theme)
    {
        recursiveDelete('themes/' . $theme);
    }
}
