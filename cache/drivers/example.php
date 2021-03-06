<?php


/*
 * khoaofgod@gmail.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 */


class phpfastcache_example extends BasePhpFastCache implements phpfastcache_driver
{
    public function checkdriver()
    {
        // return true;
        return false;
    }

    public function connectServer()
    {
    }

    public function __construct($config = array())
    {
        $this->setup($config);
        if (!$this->checkdriver() && !isset($config['skipError'])) {
            throw new Exception("Can't use this driver for your website!");
        }
    }

    public function driver_set($keyword, $value = "", $time = 300, $option = array())
    {
        if (isset($option['skipExisting']) && $option['skipExisting'] == true) {
            // skip driver
        } else {
            // add driver
        }
    }

    public function driver_get($keyword, $option = array())
    {
        // return null if no caching
        // return value if in caching

        return null;
    }

    public function driver_delete($keyword, $option = array())
    {
    }

    public function driver_stats($option = array())
    {
        $res = array(
            "info"  => "",
            "size"  =>  "",
            "data"  => "",
        );

        return $res;
    }

    public function driver_clean($option = array())
    {
    }

    public function driver_isExisting($keyword)
    {
    }
}
