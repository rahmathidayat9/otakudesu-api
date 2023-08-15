<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

class HomeController extends Controller
{
    public $url;
    public $animeUrl;
    public $episodeUrl;

    public function __construct()
    {
        $this->url = 'https://otakudesu.lol';
        $this->animeUrl = "/anime"."/";
        $this->episodeUrl = "/episode"."/";
    }

    public function index()
    {
        $url = $this->url;
        $client = new Client();
        $crawler = $client->request('GET', $url);

        $data = $crawler->filter('.thumb')->each(function ($node) use ($url, $client) {
            $linkNode = $node->filter('a');
            $link = $linkNode->attr('href');

            $imageNode = $node->filter('.thumbz img');
            $src = $imageNode->attr('src');

            $titleNode = $node->filter('.thumbz h2.jdlflm');
            $title = $titleNode->text();

            $baseURL = $url;
            $cleanedLink = str_replace($baseURL, '', $link);

            return [
                'title' => $title,
                'img_src' => $src,
                'link' => $link,
                'clean_link' => $cleanedLink,
            ];
        });

        return response()->json($data);
    }

    public function animeDetail($url)
    {
        $url = $this->url.$this->animeUrl.$url;
        $client = new Client();
        $crawler = $client->request('GET', $url);

        $episodeLinks = $crawler->filter('.episodelist ul li')->each(function ($node) use($url) {
            $link = $node->filter('a')->attr('href');
            $name = $node->filter('a')->text();
            $releaseDate = $node->filter('.zeebr')->text();
            
            $baseURL = $this->url."/episode";
            $cleanedLink = str_replace($baseURL, '', $link);

            return [
                'link' => $link,
                'clean_link' => $cleanedLink,
                'name' => $name,
                'release_date' => $releaseDate,
            ];
        });

        $data = $crawler->filter('.fotoanime')->each(function ($node) {
            $imageNode = $node->filter('img');
            $src = $imageNode->attr('src');
    
            $infoPairs = [];
            $infoNodes = $node->filter('.infozingle p span b');
            
            $infoNodes->each(function ($infoNode, $i) use (&$infoPairs) {
                $infoName = $infoNode->text();
                $infoValue = $infoNode->closest('p')->text();
    
                $infoPairs[] = [
                    'name' => $infoName,
                    'value' => $infoValue,
                ];
            });
    
            return [
                'img_src' => $src,
                'info_pairs' => $infoPairs,
            ];
        });
    
        return response()->json([
            'data' => $data,
            'eps_link' => $episodeLinks,
        ]);
    }

    public function animeEpisodeDetail($url)
    {
        $url = $this->url.$this->episodeUrl.$url;
        $client = new Client();
        $crawler = $client->request('GET', $url);

        $prevEpisodeLink = $crawler->filter('.prevnext .flir a')->eq(0)->attr('href');
        $nextEpisodeLink = $crawler->filter('.prevnext .flir a')->eq(1)->attr('href');

        $eps_links = [];
        $episodeLinks = $crawler->filter('.prevnext .fleft select option')->each(function ($node) {
            return $node->attr('value');
        });

        foreach ($episodeLinks as $link) {
            $eps_links[] = $link;
        }

        $title = $crawler->filter('.posttl')->text();
        $iframeSrc = $crawler->filter('.responsive-embed-stream iframe')->attr('src');

        return response()->json([
            'all_eps_link' => $eps_links,
            'prev_eps_link' => $prevEpisodeLink,
            'next_eps_link' => $nextEpisodeLink,
            'title' => $title,
            'iframe_src' => $iframeSrc,
        ]);
    }
}
