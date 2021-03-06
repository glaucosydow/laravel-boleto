<?php
namespace Eduardokum\LaravelBoleto\Boleto\Banco;

use Eduardokum\LaravelBoleto\Boleto\AbstractBoleto;
use Eduardokum\LaravelBoleto\Contracts\Boleto\Boleto as BoletoContract;
use Eduardokum\LaravelBoleto\Util;

class Sicredi extends AbstractBoleto implements BoletoContract
{

    /**
     * Local de pagamento
     * @var string
     */
    protected $localPagamento = 'Pagável preferencialmente nas cooperativas de crédito do sicredi';
    /**
     * Código do banco
     * @var string
     */
    protected $codigoBanco = self::COD_BANCO_SICREDI;
    /**
     * Define as carteiras disponíveis para este banco
     * @var array
     */
    protected $carteiras = ['1', '2', '3'];
    /**
     * Espécie do documento, coódigo para remessa
     * @var string
     */
    protected $especiesCodigo = [
        'DMI' => 'A', // Duplicata Mercantil por Indicação
        'DM' => 'A', // Duplicata Mercantil por Indicação
        'DR' => 'B', // Duplicata Rural
        'NP' => 'C', // Nota Promissória
        'NR' => 'D', // Nota Promissória Rural
        'NS' => 'E', // Nota de Seguros
        'RC' => 'G', // Recibo
        'LC' => 'H', // Letra de Câmbio
        'ND' => 'I', // Nota de Débito
        'DSI' => 'J', // Duplicata de Serviço por Indicação
        'OS' => 'K', // Outros
    ];
    /**
     * Se possui registro o boleto (tipo = 1 com registro e 3 sem registro)
     * @var bool
     */
    protected $registro = true;
    /**
     * Código do posto do cliente no banco.
     * @var int
     */
    protected $posto;
    /**
     * Byte que compoe o nosso número.
     * @var int
     */
    protected $byte = 2;
    /**
     * Define se possui ou não registro
     *
     * @param bool $registro
     * @return $this
     */
    public function setComRegistro(bool $registro)
    {
        $this->registro = $registro;
        return $this;
    }
    /**
     * Retorna se é com registro.
     *
     * @return bool
     */
    public function isComRegistro()
    {
        return $this->registro;
    }
    /**
     * Define o posto do cliente
     *
     * @param int $posto
     * @return $this
     */
    public function setPosto($posto)
    {
        $this->posto = $posto;
        return $this;
    }
    /**
     * Retorna o posto do cliente
     *
     * @return int
     */
    public function getPosto()
    {
        return $this->posto;
    }
    /**
     * Define o byte
     *
     * @param int $byte
     * @return $this
     */
    public function setByte($byte)
    {
        if ($byte > 9)
        {
            throw new \Exception('O byte deve ser compreendido entre 1 e 9');
        }
        $this->byte = $byte;
        return $this;
    }
    /**
     * Retorna o byte
     *
     * @return int
     */
    public function getByte()
    {
        return $this->byte;
    }
    /**
     * Método que valida se o banco tem todos os campos obrigadotorios preenchidos
     */
    public function isValid()
    {
        if ($this->byte == '' || $this->posto == '' || !parent::isValid())
        {
            return false;
        }
        return true;
    }
    /**
     * Retorna o campo Agência/Beneficiário do boleto
     *
     * @return string
     */
    public function getAgenciaCodigoBeneficiario()
    {
        return sprintf('%04s.%02s.%05s', $this->getAgencia(), $this->getPosto(), $this->getConta());
    }
    /**
     * Gera o Nosso Número.
     *
     * @return string
     */
    protected function gerarNossoNumero()
    {
        $agencia = Util::numberFormatGeral($this->getAgencia(), 4);
        $posto = Util::numberFormatGeral($this->getPosto(), 2);
        $conta = Util::numberFormatGeral($this->getConta(), 5);
        $ano = $this->getDataDocumento()->format('y');
        $byte = $this->getByte();
        $numero = Util::numberFormatGeral($this->getNumeroDocumento(), 5);
        $dv = $agencia . $posto . $conta . $ano . $byte . $numero;
        $nossoNumero = $ano . $byte . $numero . Util::modulo11($dv);
        return $nossoNumero;
    }
    /**
     * Método que retorna o nosso numero usado no boleto. alguns bancos possuem algumas diferenças.
     *
     * @return string
     */
    public function getNossoNumeroBoleto()
    {
        return Util::maskString($this->getNossoNumero(), '##/######-#');
    }
    /**
     * Método para gerar o código da posição de 20 a 44
     *
     * @return string
     * @throws \Exception
     */
    protected function getCampoLivre()
    {
        if ($this->campoLivre) {
            return $this->campoLivre;
        }

        $tipo_cobranca = $this->isComRegistro() ? '1' : '3';
        $carteira = Util::numberFormatGeral($this->getCarteira(), 1);
        $nosso_numero = $this->getNossoNumero();
        $agencia = Util::numberFormatGeral($this->getAgencia(), 4);
        $posto = Util::numberFormatGeral($this->getPosto(), 2);
        $conta = Util::numberFormatGeral($this->getConta(), 5);
        $possui_valor = $this->getValor() > 0 ? '1' : '0';

        $campo_livre = $tipo_cobranca . $carteira . $nosso_numero . $agencia . $posto . $conta . $possui_valor . '0';
        return $this->campoLivre = $campo_livre . Util::modulo11($campo_livre);
    }
}