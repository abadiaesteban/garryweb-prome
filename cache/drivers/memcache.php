<?php


/*
 * khoaofgod@gmail.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://faster.phpfastcache.com
 */


class phpfastcache_memcache extends BasePhpFastCache implements phpfastcache_driver
{
    public $instant;

    public function checkdriver()
    {
        // Check memcache
        if (function_exists("memcache_connect")) {
            return true;
        }
        $this->fallback = true;
        return false;
    }

    public function __construct($config = array())
    {
        $this->setup($config);
        if (!$this->checkdriver() && !isset($config['skipError'])) {
            $this->fallback = true;
        }
        if (class_exists("Memcache")) {
            $this->instant = new Memcache();
        } else {
            $this->fallback = true;
        }
    }

    public function connectServer()
    {
        $server = $this->config['memcache'];
        if (count($server) < 1) {
            $server = array(
                array("127.0.0.1",11211),
            );
        }

        foreach ($server as $s) {
            $name = $s[0]."_".$s[1];
            if (!isset($this->checked[$name])) {
                try {
                    if (!$this->instant->addserver($s[0], $s[1])) {
                        $this->fallback = true;
                    }

                    $this->checked[$name] = 1;
                } catch (Exception $e) {
                    $this->fallback = true;
                }
            }
        }
    }

    public function driver_set($keyword, $value = "", $time = 300, $option = array())
    {
        $this->connectServer();

        // Memcache will only allow a expiration timer less than 2592000 seconds,
        // otherwise, it will assume you're giving it a UNIX timestamp.
        if ($time>2592000) {
            $time = time()+$time;
        }
            
        if (isset($option['skipExisting']) && $option['skipExisting'] == true) {
            return $this->instant->add($keyword, $value, false, $time);
        } else {
            return $this->instant->set($keyword, $value, false, $time);
        }
    }

    public function driver_get($keyword, $option = array())
    {
        $this->connectServer();

        // return null if no caching
        // return value if in caching

        $x = $this->instant->get($keyword);

        if ($x == false) {
            return null;
        } else {
            return $x;
        }
    }

    public function driver_delete($keyword, $option = array())
    {
        $this->connectServer();
        $this->instant->delete($keyword);
    }

    public function driver_stats($option = array())
    {
        $this->connectServer();
        $res = array(
            "info"  => "",
            "size"  =>  "",
            "data"  => $this->instant->getStats(),
        );

        return $res;
    }

    public function driver_clean($option = array())
    {
        $this->connectServer();
        $this->instant->flush();
    }

    public function driver_isExisting($keyword)
    {
        $this->connectServer();
        $x = $this->get($keyword);
        if ($x == null) {
            return false;
        } else {
            return true;
        }
    }
}
