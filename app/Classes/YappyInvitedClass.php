<?php
namespace App\Classes;

/**
 * INVITED_YAPPY_ID_DEL_COMERCIO=ID // ID del comercio
 * INVITED_YAPPY_CLAVE_SECRETA=CLAVE // Clave secreta
 * INVITED_YAPPY_MODO_DE_PRUEBAS=false // Al colocar el valor true, se realizarán compras de pruebas
 * INVITED_YAPPY_PLUGIN_VERSION=P1.0.0 // Versión actual del plugin de Yappy
 */
use App\Classes\Bg\BgFirma;
class YappyInvitedClass 
{
    private $protocol;

    private $domain;

    // verificar credenciales
    private $response;

    private $paymentParameters = [];
    //const SUCCESSURL = "http://www.api.kamgus.com/usuario-v2/index.php/kamgus/yappy_success_invitado";
    private $SUCCESSURL = "";
    private $FAILURL = "";
    private static $instance = null;

    //const FAILURL = "http://www.api.kamgus.com/usuario-v2/index.php/kamgus/yappy_fail_invitado";
    const DOMAIN = "https://invitados.kamgus.com";
    function validateHash()
    {
        try {
            
            $orderId = ($_GET['orderid']);
            $status = $_GET['status'];
            $hash = $_GET['hash'];
            $domain = $_GET['domain'];
            $values = base64_decode(env("INVITED_YAPPY_CLAVE_SECRETA"));
            $secrete = explode('.', $values);
            $signature =  hash_hmac('sha256', $orderId . strtoupper($status) . $domain, $secrete[0]);
            $success = strcmp($hash, strtolower($signature)) === 0;

        } catch (\Throwable $th) {
            $success = false;
            echo $th->getMessage();
        }
        
        return $success;
    }
    public function __construct()
    {
        $this->SUCCESSURL = url("api/v2/invited/transaction_status/yappy_success");
        $this->FAILURL = url("api/v2/invited/transaction_status/yappy_fail");
        // verificar credenciales
        $this->protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || ($_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
        //$this->domain = $this->protocol . $_SERVER['HTTP_HOST'];
        $this->domain = self::DOMAIN;
        
        
              
    }
    public function startPayment(){
        $this->response = BgFirma::checkCredentials(env("INVITED_YAPPY_ID_DEL_COMERCIO"), env("INVITED_YAPPY_CLAVE_SECRETA"), $this->domain);
        //die( $this->domain);
        //die(json_encode([env("INVITED_YAPPY_ID_DEL_COMERCIO"), env("INVITED_YAPPY_CLAVE_SECRETA"), $this->domain]));
        //die(json_encode($this->response));
        if ($this->response && $this->response['success']) {
            // Inicializar objeto para poder generar el hash
            
            $bg = new BgFirma(
                $this->paymentParameters["total"],
                env("INVITED_YAPPY_ID_DEL_COMERCIO"),
                'USD',
                $this->paymentParameters["subtotal"],
                $this->paymentParameters["taxes"],
                $this->response['unixTimestamp'],
                'YAP',
                'VEN',
                $this->paymentParameters["orderId"],
                $this->paymentParameters["successUrl"],
                $this->paymentParameters["failUrl"],
                $this->domain,
                env("INVITED_YAPPY_CLAVE_SECRETA"),
                env("INVITED_YAPPY_MODO_DE_PRUEBAS"),
                $this->response['accessToken'],
                $this->paymentParameters["tel"]
            );
            $this->response = $bg->createHash();
            return $this->response;
            //if ($this->response['success']) {
            //    /**
            //     * Al verificar si se creó con éxito el hash, podremos obtener el url de la siguiente manera
            //     * @var response contiente los valores
            //     * @var response['url'] = contiene el url a redireccionar para continuar con el pago.
            //     */
            //    $url = $this->response['url'];
            //    return $url;
            //} else {
            //    /**
            //     * Aquí es donde se mostrarán los errores generados por
            //     * cualquier tipo de validación de campos necesarios para realizar la transacción.
            //     * @var response contiene los valores
            //     * @var response['msg'] = contiene el mensaje de error a mostrar
            //     * @var response['class'] = contiene la clase de error que es, pueden ser: alert (amarillo), invalid (rojo)
            //     */
            //    $bg->showAlertError($this->response);
            //}
        } else {
            /*
            echo '<style>';
            include 'main.css';
            echo '</style>';
            echo "<div class='alert'>Algo salió mal. Contacta con el administrador</div>";
            */
            return [
                "msg" => "Credencial invalida. Contacta con el administrador",
                "class" => "warning",
            ];
        }
         
    }
    public function validatePayment(){
        if (isset($_GET['orderid']) && isset($_GET['status']) && isset($_GET['domain']) && isset($_GET['hash'])) {
            header('Content-Type: application/json');
            $success = $this->validateHash();
            if ($success) {
                // Si es true, se debe cambiar el estado de la orden en la base de datos
            }
            return(['success' => $success]);
        } 
        return null;
    }
    public function setPaymenParameters($total, $subtotal, $taxes, $orderId, $tel){
        $values = base64_decode(env("INVITED_YAPPY_CLAVE_SECRETA"));
        $secrete = explode('.', $values);
        $signature = hash_hmac('sha256', $orderId . "E" . $this->domain, $secrete[0]);
        $this->paymentParameters["total"] = $total;
        $this->paymentParameters["subtotal"] = $subtotal;
        $this->paymentParameters["taxes"] = $taxes;
        $this->paymentParameters["orderId"] = $orderId;
        $this->paymentParameters["successUrl"] = $this->SUCCESSURL.'?status=E&orderId='.($orderId).'&hash='.$signature.'&domain='.self::DOMAIN;
        $this->paymentParameters["failUrl"] = $this->FAILURL.'?status=C&orderId='.($orderId).'&domain='.self::DOMAIN;
        $this->paymentParameters["tel"] = substr($tel,-8);
        return $this;
    }

    public static function getInstance(){
        if(empty(self::$instance)){
            self::$instance = new YappyInvitedClass();
        }
        return self::$instance;
    }
}
