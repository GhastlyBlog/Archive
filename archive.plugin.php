<?php

class Archive extends \Ghastly\Plugin\Plugin {
    public $events;

    public function __construct()
    {
        $this->events = [
            ['event'=>'Ghastly.PreRoute', 'func'=>'onPreRoute'],
            ['event'=>'Ghastly.PreRender', 'func'=>'onPreRender']
        ];
    }

    /** Respond to new routes **/
    public function onPreRoute(\Ghastly\Event\PreRouteEvent $event)
    {
        $event->router->with('/archive', function() use ($event) {
            $posts = $event->postModel->findAllHeaders(0);

            $event->renderer->addTemplateDir('plugins/archive');
            $event->renderer->setTemplate('archive.html');

            $event->router->respond('/?', function() use ($posts, $event){
                $event->renderer->setTemplateVar('archive_links', $this->generateLinks($posts, date('m'), date('Y')));
                $event->renderer->setTemplateVar('posts', $this->getPostsForMonthYear($event->postModel, $posts, date('m'), date('Y')));                
            });

            $event->router->respond('/[:month]/[:year]', function($req) use ($posts, $event){
                $event->renderer->setTemplateVar('archive_links', $this->generateLinks($posts, $req->month, $req->year));
                $event->renderer->setTemplateVar('posts', $this->getPostsForMonthYear($event->postModel, $posts, $req->month, $req->year));
            });
        });
    }

    /** Add variables to all templates on all routes **/
    public function onPreRender(\Ghastly\Event\PreRenderEvent $event)
    {
        $event->renderer->setTemplateVar('archives_url', 'archive');
    }

    public function getPostsForMonthYear($postModel, $posts, $month, $year)
    {
        $posts = array_filter($posts, function($post) use ($month, $year) {;
            $post_month = $post->getDate()->format('m');
            $post_year  = $post->getDate()->format('Y');

            return $post_month == $month && $post_year == $year;
        });

        foreach($posts as $key => $post) {
            $posts[$key] = $postModel->getPostById($post->getFilename());
        }

        return $posts;
    }

    public function generateLinks($posts, $active_month=false, $active_year=false)
    {
        $month_year = $posts[0]->getDate()->format('Y-m');
        $counts = $links = [];

        foreach($posts as $post) {
            $post_month_year = $post->getDate()->format('Y-m');

            if($post_month_year == $month_year) {
                $counts[$month_year] = (isset($counts[$month_year])) ? ++$counts[$month_year] : 1;
            } else {
                $counts[$post_month_year] = (isset($counts[$post_month_year])) ? ++$counts[$post_month_year] : 1;
            } 

            $month_year = $post_month_year;
        }
        
        foreach($counts as $key => $count) {
            $date = new DateTime($key.'-01');
            $links[] = [
                'month_name'=>$date->format('F Y'), 
                'month'=>$date->format('m'), 
                'year'=>$date->format('Y'), 
                'num_posts'=>$count,
                'active' => ($date->format('m-Y')==$active_month.'-'.$active_year) ? true : false
            ];
        } 

        return $links;
    }
}