<?php

include 'Prestamo.php';

if(isset($_POST['saldo'], $_POST['cuotas'], $_POST['cuota'])){
	
	$saldo = $_POST['saldo'];
	$cuotas = $_POST['cuotas'];
	$cuota = strval($_POST['cuota']);

	$prestamo = new Prestamo(10);
	// Configuramos el valor que pedimos de préstamo.
	$prestamo->setCapital($saldo);
	// Establecemos como vamos a pagar el préstamo.
	// En este caso de la cuota 1 a la 10 $ 1200.
	// Se pueden definir varios tramos de pago de distinto valor.
	
	
	$prestamo->pagos(1, $cuotas, $cuota);
	// Calculamos la tasa de interes que nos estan cobrando.
	$b = $prestamo->calcularTasaInteres();
	// Mostramos la tasa de interés mensual
	echo '<br>T.int.mensual: ' . $b;
	// con el metodo convertirTasa la pasamos a anual y la mostramos.
	// Pasamos la tasa $b, en la unidad que esta expresada 1 mes y el año son 12 meses.
	echo '<br>T.int.anual: ' . $prestamo->convertirTasa($b, 1, 12);
	// Calculamos la tabla de pagos
	$t = $prestamo->calcTablaDePagos();
	// Y la mostramos como html.
	$prestamo->getHtmlPrestamo(); 	
}
