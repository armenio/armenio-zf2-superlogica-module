<?php
namespace Armenio\Superlogica;

use Zend\Http\Client;
use Zend\Http\Client\Adapter\Curl;
use Zend\Json\Json;

/**
 * Superlogica
 * 
 * @author Rafael Armenio <rafael.armenio@gmail.com>
 * @version 1.0
 */
class Superlogica
{
	protected $_authHeader = array();

	/**
	 * Constructor
	 * 
	 * @param array $options
	 * @return __construct
	 */
	public function __construct($options = array())
	{
		$this->_authHeader = array(
			sprintf('app_token: %s', $options['app_token']),
			sprintf('access_token: %s', $options['access_token']),
		);
	}

	public function request($service, array $params = array(), $method = 'POST')
	{
		$result = false;

		try{
			$uri = sprintf('https://api.superlogica.net/v2/financeiro%s', $service);
			$client = new Client($uri);
			$client->setAdapter(new Curl());
			$client->setMethod($method);
			$client->setOptions(array(
				'curloptions' => array(
					CURLOPT_HEADER => false,
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_SSL_VERIFYHOST => false, 
				),
			));

			$client->setHeaders($this->_authHeader);
			
			if( ! empty($params) ){
				if( $method == 'GET' ){
					$client->setParameterGet($params);
				}else{
					$client->setParameterPost($params);
				}
			}
			
			$response = $client->send();

			if ($response->isSuccess()) {
				$body = $response->getContent();
				$json = Json::decode($body, 1);
				
				if( ! empty($json[0]['status']) ){
					if( $json[0]['status'] == '200' ){
						$result = $json;
					}
				}else{
					$result = $json;
				}
			}

			$isException = false;
		} catch (\Zend\Http\Exception\RuntimeException $e){
            $isException = true;
        } catch (\Zend\Http\Client\Adapter\Exception\RuntimeException $e){
        	$isException = true;
        } catch (\Zend\Json\Exception\RuntimeException $e) {
			$isException = true;
		} catch (\Zend\Json\Exception\RecursionException $e2) {
			$isException = true;
		} catch (\Zend\Json\Exception\InvalidArgumentException $e3) {
			$isException = true;
		} catch (\Zend\Json\Exception\BadMethodCallException $e4) {
			$isException = true;
		}

		if( $isException === true ){
			//código em caso de problemas no decode
		}

		return $result;
	}

	public function post($service, array $params = array())
	{
		return $this->request($service, $params, 'POST');
	}

	public function put($service, array $params = array())
	{
		return $this->request($service, $params, 'PUT');
	}

	public function get($service, array $params = array())
	{
		return $this->request($service, $params, 'GET');
	}

	public function salvarAssinantes(array $dadosAssinante)
	{
		$dadosAssinante['cnpj'] = mb_substr($dadosAssinante['cnpj'], -18);

		$params = array(
			'ST_NOME_SAC' => $dadosAssinante['titulo'],
			'ST_NOMEREF_SAC' => $dadosAssinante['nome_fantasia'],
			'ST_CGC_SAC' => $dadosAssinante['cnpj'],
			//'ST_INSCMUNICIPAL_SAC' => $dadosAssinante['inscricao_municipal'],
			'ST_INSCRICAO_SAC' => $dadosAssinante['inscricao_estadual'],
			'ST_EMAIL_SAC' => $dadosAssinante['email'],
			'ST_TELEFONE_SAC' => $dadosAssinante['telefone'],
			'ST_FAX_SAC' => $dadosAssinante['celular'],
			'ST_DIAVENCIMENTO_SAC' => $dadosAssinante['dia_vencimento'],
		);

		if( ! empty($dadosAssinante['id_externo']) ){
			$params['ID_SACADO_SAC'] = $dadosAssinante['id_externo'];

			$response = $this->put('/clientes', $params);
		}else{
			$response = $this->post('/clientes', $params);
		}

		if( ! empty($response[0]['data']['id_sacado_sac']) ){
			return $response[0]['data']['id_sacado_sac'];
		}

		return $response;
	}

	public function salvarAssinanteEnderecos(array $dadosAssinante)
	{
		return $this->put('/clientes', array(
			'ID_SACADO_SAC' => $dadosAssinante['id_externo'],

			'ST_CEP_SAC' => $dadosAssinante['ComponentAssinanteEnderecos'][0]['cep'],
			'ST_ENDERECO_SAC' => $dadosAssinante['ComponentAssinanteEnderecos'][0]['endereco'],
			'ST_NUMERO_SAC' => $dadosAssinante['ComponentAssinanteEnderecos'][0]['numero'],
			'ST_BAIRRO_SAC' => $dadosAssinante['ComponentAssinanteEnderecos'][0]['ComponentGeoBairros']['titulo'],
			'ST_COMPLEMENTO_SAC' => $dadosAssinante['ComponentAssinanteEnderecos'][0]['complemento'],
			'ST_CIDADE_SAC' => $dadosAssinante['ComponentAssinanteEnderecos'][0]['ComponentGeoCidades']['titulo'],
			'ST_ESTADO_SAC' => $dadosAssinante['ComponentAssinanteEnderecos'][0]['ComponentGeoEstados']['uf'],
		));
	}

	public function dadosAssinante(array $dadosAssinante)
	{
		return $this->get(sprintf('/clientes/%s', $dadosAssinante['id_externo']));
	}

	public function salvarPlanos(array $dadosPlanos)
	{
		$params = array(
			'ST_NOME_PLA' => $dadosPlanos['titulo'],
			'ID_GRADE_GPL' => 1,
			'ST_DESCRICAO_PLA' => $dadosPlanos['descricao'],
			'ID_CONTA_CB' => 1,
			'FL_DESTAQUE_PLA' => $dadosPlanos['destaque'],
			'FL_BOLETO_PLA' => $dadosPlanos['boleto'],
			'COMPO_RECEBIMENTO' => array(
				array(
					'ID_PRODUTO_PRD' => 999999982,
					'ST_COMPLEMENTO_COMP' => 0,
					'NM_QUANTIDADE_COMP' => 1,
					'VL_UNITARIO_PRD' => $dadosPlanos['preco'],
				),
			),
		);

		if( ! empty($dadosPlanos['id_externo']) ){
			$params['ID_PLANO_PLA'] = $dadosPlanos['id_externo'];

			$response = $this->put('/planos', $params);
		}else{
			$response = $this->post('/planos', $params);
		}

		if( ! empty($response[0]['data']['id_plano_pla']) ){
			return $response[0]['data']['id_plano_pla'];
		}

		return $response;
	}

	public function dadosPlano(array $dadosPlanos)
	{
		return $this->get(sprintf('/planos/%s', $dadosPlanos['id_externo']));
	}

	public function salvarAssinaturas(array $dadosAssinaturas)
	{
		$params = array(
			'PLANOS' => array(
				array(
					'ID_SACADO_SAC' => $dadosAssinaturas['ComponentAssinantes']['id_externo'],
					'ID_PLANO_PLA' => $dadosAssinaturas['ComponentPlanos']['id_externo'],
					'DT_CONTRATO_PLC' => date('m/d/Y', $dadosAssinaturas['data_hora']->getTimestamp()),
				),
			),
		);

		$response = $this->post('/assinaturas', $params);

		return $response;
	}
}