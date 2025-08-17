<?php namespace UptimeStatus;

use UptimeStatus\Model\Page;

class Status {

    readonly int $backend_id;
    readonly string $slug;
    public ?Page $page = null;

    public function __construct(int $backend_id, string $slug) {
        $this->backend_id = $backend_id;
        $this->slug = $slug;
    }

    public function get_page(): ?Page {
        $this->page = Page::get($this, $this->backend_id, $this->slug);
        return $this->page;
    }

    public function display(): void {
        if ($this->page == null) return;
        $data = $this->page->export();
        $this->render($data);
    }

    public function display_fallback(): void {
        $group = new \UptimeStatus\Model\Group("Unavailable");
        $monitor = new \UptimeStatus\Model\Monitor(
            "Unable to connect to the server.",
            0.0,
            [["status" => 0, "time" => time()]],
            []
        );
        $group->add_monitor($monitor);

        $page = new \UptimeStatus\Model\Page(["title" => ucfirst($this->slug)]);
        $page->add_group($group);

        $data = $page->export();
        $data["nav"] = Config::get("nav");

        $this->render($data);
    }


    private function render(array $data): void {
        $twig_config = [];
        if (Config::get("enable_twig_cache")) $twig_config["cache"] = "../cache/twig/";

        $loader = new \Twig\Loader\FilesystemLoader(dirname(__DIR__) . "/view/");
        $twig = new \Twig\Environment($loader, $twig_config);

        $twig->addFilter(Filters::globalstatus());
        $twig->addFilter(Filters::statusicon());
        $twig->addFilter(Filters::statuscolor());

        $locale = new Locale(Config::get("default_language"));
        $twig->addFilter($locale->t());

        $ext = $twig->getExtension(\Twig\Extension\CoreExtension::class);
        $ext->setDateFormat($locale->get("dateformat"));
        $ext->setTimezone(Config::get("timezone"));

        echo $twig->render('index.twig', $data);
    }
}
