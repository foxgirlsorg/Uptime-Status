<?php namespace UptimeStatus;

class Router {

    public function get_path(): string {
        $url = $_SERVER["REQUEST_URI"];
        $parsed_url = parse_url($url);
        $path = "";
        if (isset($parsed_url["path"])) {
            $path = substr($parsed_url["path"], 1);
        }
        if ($path == "" && isset($_GET["q"])) {
            $path = "{$_GET["q"]}";
        }
        return $path;
    }

    public function get_page($path): string {
        $pages = Config::get("pages");
        $slugs = array_keys($pages);
        foreach ($slugs as $page) {
            if ($path == $page) {
                return $page;
            }
        }
        return $slugs[0];
    }

    public function render() {
        $path = $this->get_path();
        $slug = $this->get_page($path);

        $backend_ids = Config::get("pages")[$slug];
        if (!is_array($backend_ids)) $backend_ids = [$backend_ids];

        foreach ($backend_ids as $bid) {
            $status = new Status($bid, $slug);
            $page = $status->get_page();
            if ($page) {
                $status->display();
                return;
            }
        }

        // all backends failed → fallback page
        $status = new Status($backend_ids[0], $slug);
        $status->display_fallback();
    }

}
