<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class APIControllerTest extends WebTestCase
{
    public function testShortestPath_InvalidRequest()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/path/anorgan');
        $response = $client->getResponse();

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testShortestPath_User1NotFound()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/path/ali/0cool-f');
        $response = $client->getResponse();

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertEquals('{"ack":"Error","message":"Could not find any contributor for username: ali"}', $response->getContent());
    }

    public function testShortestPath_User2NotFound()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/path/anorgan/ali');
        $response = $client->getResponse();

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertEquals('{"ack":"Error","message":"Could not find any contributor for username: ali"}', $response->getContent());
    }

    public function testShortestPath_SameUsers()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/path/anorgan/anorgan');
        $response = $client->getResponse();

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertEquals('{"ack":"Error","message":"Usernames are the same!"}', $response->getContent());
    }

    public function testShortestPath_NotConnected()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/path/ss89/zantdev');
        $response = $client->getResponse();

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertEquals('{"ack":"Error","message":"users \u0027ss89\u0027 and \u0027zantdev\u0027 are not connected!"}', $response->getContent());
    }

    public function testShortestPath_Successful()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/path/anorgan/0cool-f');
        $response = $client->getResponse();

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('{"ack":"OK","path_len":2,"path":["filipve\/laravel-translation-manager","0cool-f\/banbuilder"]}', $response->getContent());
    }

    public function testPotentials_InvalidRequest()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/packages/lukebro/potentials');
        $response = $client->getResponse();

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testPotentials_PackageNotFound()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/packages/testing/testing/potentials');
        $response = $client->getResponse();

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertEquals('{"ack":"Error","message":"Package not found!"}', $response->getContent());
    }

    public function testPotentials_NoNeighbor_Successful()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/packages/lukebro/flash/potentials');
        $response = $client->getResponse();

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('{"ack":"OK","potential_contributors":[{"name":"scrutinizer-auto-fixer","number of packages":"1203"},{"name":"GrahamCampbell","number of packages":"909"},{"name":"pborreli","number of packages":"677"},{"name":"stof","number of packages":"634"},{"name":"invalid-email-address","number of packages":"558"},{"name":"bitdeli-chef","number of packages":"552"},{"name":"gitter-badger","number of packages":"453"},{"name":"cordoval","number of packages":"451"},{"name":"Nyholm","number of packages":"412"},{"name":"Seldaek","number of packages":"374"},{"name":"barryvdh","number of packages":"336"},{"name":"TomasVotruba","number of packages":"314"},{"name":"lsmith77","number of packages":"302"},{"name":"igorw","number of packages":"285"},{"name":"fabpot","number of packages":"281"},{"name":"beberlei","number of packages":"261"},{"name":"weierophinney","number of packages":"259"},{"name":"sagikazarmark","number of packages":"258"},{"name":"schmittjoh","number of packages":"256"},{"name":"Ocramius","number of packages":"252"}]}', $response->getContent());
    }
    public function testPotentials_WithNeighbor_Successful()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/api/packages/zzk/vim/potentials');
        $response = $client->getResponse();

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('{"ack":"OK","potential_contributors":{"ryross":1,"druid628":1,"rlweb":1,"brainbowler":1,"scrutinizer-auto-fixer":1,"sergeyklay":1,"afbora":1,"xboston":1,"rualatngua":1,"oleksandr-torosh":1,"Geo-i":1,"thinhvoxuan":1,"pletsky":1,"aydancoskun":1,"djavolak":1,"Narrator69":1,"htejeda":1,"gialachoanglong":1}}', $response->getContent());
    }
}
