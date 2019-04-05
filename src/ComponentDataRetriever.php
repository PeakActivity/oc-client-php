<?php

namespace OpenComponents;

use GuzzleHttp\Client as GuzzleClient;
use OpenComponents\Model\Component;

class ComponentDataRetriever
{
    private $components;

    private $httpClient;

    public function __construct($config)
    {
        foreach ($config['components'] as $component) {
            $this->components[] = [
                'name' => $component
            ];
        }

        $this->httpClient = new GuzzleClient([
            'base_uri' => $config['registries']['serverRendering']
        ]);
    }

    public function performRequest($components = null)
    {
        if (!$components) {
            $components = $this->components;
        }

        $mappingUtils = new MappingUtils();

        $httpMethod = (1 < count($components)) ? "post" : "get";
        //only allow them to define an httpMethod if there is one component
        if(count($components) === 1){
            $tmp = $mappingUtils->arrayToComponent($components[0]);
            if($tmp ->getParameters() && isset($tmp ->getParameters()['httpMethod']))  {
                $targetMethod = strtolower($tmp ->getParameters()['httpMethod']);
                if($targetMethod === 'post'){
                    $httpMethod = $targetMethod;
                }
            }
        }

        if(strtolower($httpMethod) === 'post'){
            $compsArr = [];
            $response = json_decode($this->performPost($components));
            if (!$response) {
                return;
            }
            foreach ($response as $comp) {
                $compsArr[] = $comp->response->html;
            }
            return $compsArr;
        }

        $response = json_decode(
            $this->performGet (
                $mappingUtils->arrayToComponent($components[0])
            )
        );
       
        if ($response) {
            return $response->html;
        }

        return;
    }

    /**
     * performGet
     *
     * @param Component $component
     * @access public
     * @return Object
     */
    public function performGet(Component $component)
    {
        $path = $component->getName();

        if ($component->getVersion() !== '') {
            $path .= '/' . $component->getVersion();
        }


        $response = $this->httpClient->request('GET', $path, [
            'query' => $component->getParameters()
        ]);

        return (string) $response->getBody();
    }

    /**
     * performPost
     *
     * @param array $components
     * @access public
     * @return void
     */
    public function performPost(array $components)
    {
        $response = $this->httpClient->post('', [
            'json' => ['components' => $components]
        ]);

        return (string) $response->getBody();
    }

    /**
     * setHttpClient
     *
     * @param GuzzleClient $httpClient
     * @access public
     * @return ComponentDataRetriever
     */
    public function setHttpClient(GuzzleClient $httpClient)
    {
        $this->httpClient = $httpClient;

        return $this;
    }
}
