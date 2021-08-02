<?php

namespace App\Services;


use GuzzleHttp\Exception\ConnectException;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Http;

class FlightsService
{
    protected $endpoint;
    protected $response;
    protected $flights = array();
    protected $groupFlights = array();
    protected $fares = array();
    protected $prices = array();
    protected $paramsFind = array();
    protected $totalGroups;
    protected $totalFlights;
    protected $cheapestGroup= array();

    public function __construct()
    {
        $this->getFlightsFromAPI();
        $this->findFaresAndPrices();
        $this->getGroupFligths();
        $this->getCheapestGroup();
    }

    //Metodo para gerar um ID unico para o grupo
    private function gerarId()
    {
        return substr(md5(uniqid(rand(), true)),1,15);
    }

    //Metodo que faz a consulta na API
    private function getFlightsFromAPI()
    {
        $this->endpoint = '/flights';
        $this->response = Http::get(env('BASE_URL_API') . $this->endpoint);
        $this->parseFlightsData();
        $this->totalFlights = count($this->flights);
    }

    //Metodo para converter o json retornado da API para objeto
    private function parseFlightsData()
    {
        $this->flights = json_decode($this->response);
    }

    //Metodo para buscar todas as taxas e precos disponiveis
    private function findFaresAndPrices()
    {
        foreach ($this->flights as $flight) {
            if (isset($flight->fare)) {
                if (!in_array($flight->fare, $this->fares)) {
                    array_push($this->fares, $flight->fare);
                }
                if (!in_array($flight->price, $this->prices)) {
                    array_push($this->prices, $flight->price);
                }
            }
        }
    }

    //Metodo para buscar os voos de ida
    private function findOutboundFlights($fare, $price)
    {
        $outboundFlights = array();
        foreach($this->flights as $flight) {
            if (isset($flight->fare)) {
                if ($flight->fare == $fare && $flight->price == $price && $flight->outbound == 1) {
                    array_push($outboundFlights, $flight);
                }
            }
        }
        return $outboundFlights;
    }

    //Metodo para buscar os voos de volta
    private function findInboundFlights($fare, $price)
    {
        $inboundFlights = array();
        foreach($this->flights as $flight) {
            if (isset($flight->fare)) {
                if ($flight->fare == $fare && $flight->price == $price && $flight->inbound == 1) {
                    array_push($inboundFlights, $flight);
                }
            }
        }
        return $inboundFlights;
    }

    //fazer metodo para ver se parametro de busca ja existe
    private function paramExists($fare, $price)
    {
        $paramExists = false;
        foreach ($this->paramsFind as $param) {
            if ($param['fare'] == $fare && $param['price'] == $price) {
                $paramExists = true;
            }
        }
        return $paramExists;
    }

    //Metodo para verificar e ordenar os preços dos grupos
    private function sortGroups($group1, $group2)
    {
        if ($group1['totalPrice'] == $group2['totalPrice']) return 0;

        return ($group1['totalPrice'] > $group2['totalPrice']) ? 1 : -1;
    }

    //Metodo para fazer o agrupamento dos voos
    private function getGroupFligths()
    {
        foreach ($this->fares as $fare) {
            foreach($this->prices as $price) {
                if (!$this->paramExists($fare, $price)) {
                    array_push($this->paramsFind, array(
                        'fare' => $fare,
                        'price' => $price
                    ));
                }
            }
        }

        foreach ($this->paramsFind as $param) {
            $outboundFlights = $this->findOutboundFlights($param['fare'], $param['price']);
            if (count($outboundFlights) > 0) {
                foreach ($this->prices as $price) {
                    $inboundFlights = $this->findInboundFlights($outboundFlights[0]->fare, $price);
                    foreach($inboundFlights as $inboundFlight) {
                        array_push($this->groupFlights, array(
                            'uniqueId' => $this->gerarId(),
                            'totalPrice' => $param['price'] + $price,
                            'outbound' => $outboundFlights,
                            'inbound' => $inboundFlights
                        ));
                        break;
                    }
                }
            }
        }
        $this->totalGroups = count($this->groupFlights);
        return usort($this->groupFlights, array($this, 'sortGroups'));
    }

    //Metodo para encontrar o grupo mais barato
    private function getCheapestGroup()
    {
        foreach ($this->groupFlights as $group) {
            if (count($this->cheapestGroup) > 0) {
                if ($this->cheapestGroup['totalPrice'] > $group['totalPrice']) {
                    $this->cheapestGroup['totalPrice'] = $group['totalPrice'];
                    $this->cheapestGroup['uniqueId'] = $group['uniqueId'];
                }
            } else {
                $this->cheapestGroup['totalPrice'] = $group['totalPrice'];
                $this->cheapestGroup['uniqueId'] = $group['uniqueId'];
            }
        }
    }

    //metodo para retornar o JSON completo
    public function getFlights()
    {
        return(
            array(
                'flights' => $this->flights,
                'groups' => $this->groupFlights,
                'totalGroups' => $this->totalGroups,
                'totalFlights' => $this->totalFlights,
                'cheapestPrice' => $this->cheapestGroup['totalPrice'],
                'cheapestGroup' => $this->cheapestGroup['uniqueId']
            )
        );
    }
}
