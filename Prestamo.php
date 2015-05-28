<?php
/**
 * Clase Prestamo
 * Clase con la cual se puede realizar calculos sobre prestamos que no son 
 * a cuota constantes. Permite calculo de tasa de interes, amortizacion de un
 * periodo dado, cuota de un periodo etc.
 * Se utilizan las operaciones de precision arbitraria de php.
 * 
 * @package     Finanzas Creado para el proyecto Agenor.
 * @copyright   (2013 - 2013) - ObjetivoPHP
 * @license     Gratuito (Free) http://www.opensource.org/licenses/gpl-license.html
 * @author      Marcelo Castro, ObjetivoPHP
 * @version     0.1.0 (01/07/2013 - 01/07/2013)
 */
class Prestamo
{
    /**
     * Precision de los calculos a realizar.
     * @var integer 
     */
    private $_precision = 12;
 
    /**
     * Cantidad de iteraciones para los calculos de funciones externas.
     * @var integer 
     */
    private $_iterador  = 12;
 
    /**
     * Tasa de Interes real del Prestamo, expresada en el lapso de tiempo
     * de los pagos. Si se tiene una tasa anual y se requiere mensual usar el 
     * metodo convertirTasa.
     * @var double
     */
    private $_tasaInteres;
 
    /**
     * Monto total del prestamo solicitado al Momento 0.
     * @var double 
     */
    private $_capital;
 
    /**
     * Arreglo Conteniendo los pagos en cada periodo. El indice es el periodo y
     * el monto el valor del arreglo.
     * @example array(1 => 10000, 2=> 3750); Significa que el pago del 1er Mes es
     * 10.000 y el segundo 3.750.-
     * @var array 
     */
    private $_pagos = array();
 
    /**
     * Contiene la tabla de pagos del peridodo.
     * @var array
     */
    private $_tablaPagos = array();
 
    /**
     * Constructor de Clase.
     * @param   integer $precision  Establece con cuantos numeros se mostrara el resultado.
     */
    public function __construct($precision = 12)
    {
        if (is_int($precision) && $precision > 0) {
            $this->_precision = $precision;
        }
 
        bcscale($this->_precision);
    }
 
    /**
     * Cofigura la tasa de interes que tendra el Prestamo.
     * @param   double  $tasa       Valor de la tasa de interes.
     * @param   integer $periodos   En cuantos periodos del prestamo esta expresada.
     * @example Si la tasa nos la dan anual para un prestamo de pago mensual entonces
     *          periodos va a ser igual a 12.-
     * @throws  Si el valor de la tasa de interes no es valido lanza un error.
     */
    public function setTasaInteres($tasa, $periodos = 1)
    {
        if (!is_numeric($tasa)) {
            throw new \Exception('Tasa de Interes No Valida');
        }
 
        if (is_int($periodos)) {
            $tasa   = bcadd('1', $tasa);
            $period = bcdiv('1', $periodos);
            $tasa   = $this->bcpowx($tasa, $period, $this->_iterador);
            $this->_tasaInteres = bcsub($tasa, '1');
        }
    }
 
    /**
     * Establece las iteraciones para el calculo de potencias complejas.
     * @param   integer $iterador   Numero de iteraciones a realizar.
     */
    public function setIterador($iterador)
    {   
        if (is_int($iterador) && $iterador >= 10) {
            $this->_iterador = $iterador;
        }
    }
 
    /**
     * Convierte la tasa de interes de un periodo a otro.
     * @param   double  $tasa
     * @param   integer $de     periodo actual de la tasa de interes (generalmente 1).
     * @param   integer $a      Al periodo que se quiere convertir (Ej.: Anual = 12, si de  1).
     * @return  double          Retorna la tasa de interes convertida al Periodo establecido.
     */
    public function convertirTasa($tasa, $de, $a)
    {
        $retorno        = 0;
        if (is_int($de) && is_int($a) && is_numeric($tasa)) {
            $tasa       = bcadd('1', $tasa);
            $period     = bcdiv($a, $de);
            $tasa       = $this->bcpowx($tasa, $period, $this->_iterador);
            $retorno    = bcsub($tasa, '1');
        }
        return $retorno;
    }
 
    /**
     * Retorna la Tasa de interes Calculada.
     * @return duble
     */
    public function getTasaInteres()
    {
        return $this->_tasaInteres;
    }
 
    /**
     * Calcula la tasa Interna de Retorno de un prestamo o inversion.
     * @return double
     */
    public function calcularTasaInteres()
    {   
        if (is_numeric($this->_capital) && $this->_capital > 0 && array_sum($this->_pagos) >= $this->_capital ) {
            $tirP               = array_sum($this->_pagos) / $this->_capital;  
            $this->_tasaInteres = $this->_tir(0, $tirP, 0);             
        } else {
            throw new \Exception('Faltan parametros para calcular la tasa de interes.');
        }
 
        return $this->_tasaInteres;
    }
 
    /**
     * Calcula la tir de una inversion.
     * Se calcula en base al teorema de bolzano.
     * @param   double  $a          Extremo inferior supuesto de la Tasa de Interes.
     * @param   double  $b          Extremo superior supuesto de la tasa de interes.
     * @param   double  $tirc       Tir que esta siendo calculada.
     * @param   double  $precision  Precision que se quiere del resultado.
     * @return  double
     */
    private function _tir($a, $b, $tirc)
    {    
        $tir    = bcdiv(bcadd($a, $b), 2);
        $van    = 0;
        foreach ($this->_pagos as $cuota => $valor) {
            //$van    = $van + $valor / pow((1 + $tir ), $cuota);
            $van = bcadd($van, bcdiv($valor, bcpow(bcadd('1', $tir), $cuota)));
        }
        $van = bcsub($van, $this->_capital);
 
        if (bccomp($tirc, $tir) == 0) {
            return $tir;
        } else {
            if ($van > 0) {
                return $this->_tir($tir, $b, $tir);
            } else {
                return $this->_tir($a, $tir, $tir);
            }
        }
    }
 
    /**
     * Establece el dinero pedido en prestamo o el valor de la secuencia de
     * pagos al momento 0.
     * @param   double  $monto  Capital, dinero pedido.
     * @throws  Error si no se ingresa un monto valido de capital.
     */
    public function setCapital($monto)
    {
        if (is_numeric($monto)) {
            $this->_capital = $monto;
        } else {
            throw new \Exception('No se establecio un monto valido.');
        }
    }
 
    /**
     * Calcula la cuota de un prestamo para una cuota constante.
     * @param   integer $periodos   Cantidad de Periodos de los pagos consecutivos.
     * @return  double              Cuota a pagar en cada periodo.
     */
    public function calcCuota($periodos)
    {
        $i          = $this->_tasaInteres;
        $n          = (int) $periodos;
        if (bccomp(0, $this->_tasaInteres) == 0) {
            $cuota  = bcdiv($this->_capital, $n);
        } else {
            $numerador  = bcmul($i, bcpow(bcadd('1', $i), $n));
            $divisor    = bcsub(bcpow(bcadd('1', $i), $n), '1');
            $cuota      = bcmul($this->_capital, bcdiv($numerador, $divisor));            
        }
        return $cuota;
    }
 
    /**
     * Configura los pagos.
     * @param   integer     $desde  Cuota desde donde comienza el pago.
     * @param   integer     $hasta  Cuota hasta donde se paga.
     * @param   double      $cuota  Valor de la cuota.
     * @throws \Exception   Si no se ingresa un monto de cuota valido.
     */
    public function pagos($desde, $hasta, $cuota)
    {
        $desde = (int) $desde;
        $hasta = (int) $hasta;
 
        if (is_numeric($cuota)) {
            for($i = $desde; $i <= $hasta; $i++) {
                $this->_pagos[$i] = $cuota;
            }
        } else {
            throw new \Exception('La cuota no tiene un valor valido.');
        }
    }
 
    /**
     * Genera una tabla de Pagos de un prestamo.
     * @param   boolean $amortConst Establece si la amortizacion es constante o variable.
     * @return  array
     * @throws \Exception   Si el periodo de pago es menor o igual a 0.
     */
    public function calcTablaDePagos($amortConst = false)
    {
        $i      = $this->_tasaInteres;
        end($this->_pagos);
        $datos  = each($this->_pagos);
        $n      = $datos['key'];
        if ($n <= 0) {
            throw new \Exception('El periodo de pago no puede ser cero.');
        }
 
        if ($amortConst) {
            // Si tenemos Amortizacion Constante entonces la amortizacion es el 
            // Capital dividido el numero de cuotas, si no es asi se toma el 
            // arreglo de pagos para calcular la tabla.
            $c  = bcdiv($this->_capital, $n);
        }
 
        $tabla      = array();
        $tabla[0]['Monto']  = $this->_capital;
        $tabla[0]['Saldo']  = $this->_capital;
        for ($p = 1; $p <= $n; $p++) {
            $tabla[$p]['Periodo']   = $p;
            $tabla[$p]['Monto']     = $tabla[($p-1)]['Saldo'];
            $interes        = bcmul($tabla[$p]['Monto'], $this->_tasaInteres);
            if ($amortConst) {
                $tabla[$p]['Amortizacion']  = $c;
            } else {
                $tabla[$p]['Amortizacion']  = bcsub($this->_pagos[$p], $interes);
            }
            $tabla[$p]['Interes']       = $interes;
            $tabla[$p]['Cuota']     = bcadd($tabla[$p]['Amortizacion'], $tabla[$p]['Interes']);
            $tabla[$p]['Saldo']     = bcsub($tabla[$p]['Monto'], $tabla[$p]['Amortizacion']);
        }
        array_shift($tabla);
        $this->_tablaPagos = $tabla;
        return $tabla;
    }
 
    /**
     * Genera una tabla de prestamo.
     * Metodo usado para ver la tasa de prestamo de ejemplo.
     * No se debe incluir.
     */
    public function getHtmlPrestamo($dec = 2)
    {
        if (count($this->_tablaPagos) < 1) {
            throw new \Exception('No se creo la tabla de pagos. Ejecute calTablaDePagos');
        }
        // Genero el HTML
        echo '<table style="border:solid;" border="1">';
        echo '<td>Periodo</td><td>Monto</td><td>Amort.</td><td>Interes</td><td>Cuota</td><td>Saldo</td>';
        foreach ($this->_tablaPagos as $value) {
            echo '<tr>' . PHP_EOL;
            echo '<td>' . number_format($value['Periodo'], 0, ',', '.')         . '</td>' . PHP_EOL;
            echo '<td>' . number_format($value['Monto'], $dec, ',', '.')        . '</td>' . PHP_EOL;
            echo '<td>' . number_format($value['Amortizacion'], $dec, ',', '.') . '</td>' . PHP_EOL;
            echo '<td>' . number_format($value['Interes'], $dec, ',', '.')      . '</td>' . PHP_EOL;
            echo '<td>' . number_format($value['Cuota'], $dec, ',', '.')        . '</td>' . PHP_EOL;
            echo '<td>' . number_format($value['Saldo'] , $dec, ',', '.')       . '</td>' . PHP_EOL;
            echo '</tr>' . PHP_EOL;
        }
 
        echo '</table>';  
    }
 
 
    ############################################################################
    # FUNCIONES EXTRAIDAS DEL MANUAL DE PHP                                    #
    # http://www.php.net/manual/es/ref.bc.php                                  #
    ############################################################################
    
    /**
     * Computes the factoral (x!).
     * @author Thomas Oldbury.
     * @license Public domain.
     */
    public function bcfact($fact)
    {
        if($fact == 1) return 1;
        return bcmul($fact, $this->bcfact(bcsub($fact, '1')));
    }    
 
    /**
     * Computes e^x, where e is Euler's constant, or approximately 2.71828.
     * @author Thomas Oldbury.
     * @license Public domain.
     */
    public function bcexp($x, $iters = 7)
    {
        /* Compute e^x. */
        $res = bcadd('1.0', $x);
        for($i = 0; $i < $iters; $i++) {
            $res += bcdiv(bcpow($x, bcadd($i, '2')), $this->bcfact(bcadd($i, '2')));
        }
        return $res;
    }
 
    /**
     * Computes ln(x).
     * @author Thomas Oldbury.
     * @license Public domain.
     */
    public function bcln($a, $iters = 10)
    {
        $result = "0.0";
        for($i = 0; $i < $iters; $i++) {
            $pow = bcadd("1.0", bcmul($i, "2.0"));
            //$pow = 1 + ($i * 2);
            $mul        = bcdiv("1.0", $pow);
            $fraction   = bcmul($mul, bcpow(bcdiv(bcsub($a, "1.0"), bcadd($a, "1.0")), $pow));
            $result     = bcadd($fraction, $result);
        }
 
        $res = bcmul("2.0", $result);
        return $res;
    }
 
    /**
     * 
     * Computes a^b, where a and b can have decimal digits, be negative and/or very large.
     * Also works for 0^0. Only able to calculate up to 10 digits. Quite slow.
     * @author Thomas Oldbury.
     * @license Public domain.
     */
    public function bcpowx($a, $b, $iters = 25)
    {
        $ln = $this->bcln($a, $iters);
        return $this->bcexp(bcmul($ln, $b), $iters);
    }
}