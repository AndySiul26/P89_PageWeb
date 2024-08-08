<?php

    set_time_limit(300); // Establece el tiempo de ejecución máximo a 5 minutos
    require('librerias/tfpdf/tfpdf.php');  // Asegúrate de tener la librería tFPDF

    define('LOGO1', 'Imagenes/Logo.png');
    define('LOGO2', 'Imagenes/Logo2.png');
    define ('SLOGAN', '¡Portal 89 trasladándote al éxito!');
    define ('ANCHO_ROTULO_TABLA_ORDEN', 28);
    define ('ALTO_ROTULO_TABLA_ORDEN', 8);

    function es_diccionario($variable) {
        if (!is_array($variable)) {
            return false;
        }

        foreach (array_keys($variable) as $clave) {
            if (!is_string($clave)) {
                return false;
            }
        }

        return true;
    }


    class PDF extends tFPDF
    {
        protected $col = 0; // Columna actual
        protected $y0;      // Ordenada de comienzo de la columna
        protected $y1; // Fila actual

        protected $ante_x=0;
        protected $ante_y=0;

        protected $alto_ante_fila = 0;

        function SetCol($col)
        {
            // Establecer la posición de una columna dada
            $this->col = $col;
            $x = 10+$col*65;
            $this->SetLeftMargin($x);
            $this->SetX($x);
        }

        function AcceptPageBreak()
        {
            // Método que acepta o no el salto automático de página
            if($this->col<2)
            {
                // Ir a la siguiente columna
                $this->SetCol($this->col+1);
                // Establecer la ordenada al principio
                $this->SetY($this->y0);
                // Seguir en esta página
                return false;
            }
            else
            {
                // Volver a la primera columna
                $this->SetCol(0);
                // Salto de página
                return true;
            }
        }

        function Header()
        {
            // Logo
            $this->Image(LOGO1, 10, 6, 30);
            // Texto del encabezado
            $this->SetFont('DejaVu', 'B', 12);
            $this->SetTextColor(128, 128, 128);
            $this->Cell(0, 10, 'Fan Show Films y Portal 89', 0, 1, 'R');
            $this->Ln(10);
        }

        function TituloComprobante()
        {
            $this->SetFont('DejaVu', 'B', 16);
            $this->SetFillColor(200, 200, 200);
            $this->SetTextColor(0, 0, 0);
            $this->Cell(0, 10, 'Comprobante de Orden de Trabajo', 1, 1, 'C', true);
            $this->Ln(5);
        }

        function CampoOrdenTrabajo($rotulo, $valor, $misma_fila = false)
        {
            $this->SetFont('DejaVu', '', 8);
            $alto_txt = 0;

            if($this->alto_ante_fila == 0 || !$misma_fila)
            {
                $ancho_txt = $this->GetStringWidth($valor);
                // Convertir a enteros
                $div = $ancho_txt / ANCHO_ROTULO_TABLA_ORDEN;
                
                if ($div > (int)($div))
                {
                    $div = (int)($div) + 1;
                } 
                else
                {
                    $div = (int)($div);
                }
                
                $alto_txt = ALTO_ROTULO_TABLA_ORDEN * $div;
                $this->alto_ante_fila = $alto_txt;
            }
            else
            {
                $alto_txt = $this->alto_ante_fila;
            }

            if ($alto_txt == 0) {
                $alto_txt = ALTO_ROTULO_TABLA_ORDEN;
            }

            if($misma_fila) $this->SetXY($this->ante_x, $this->ante_y);
        
            // Posición X e Y iniciales para la fila
            $this->ante_x = $this->GetX();
            $this->ante_y = $this->GetY();
        
            // Rotulo del cliente
            $this->SetFont('DejaVu', 'B', 8);
            
            $this->MultiCell(w: ANCHO_ROTULO_TABLA_ORDEN, h: $alto_txt, txt: $rotulo, border: 1, align: 'L', fill: true);
        
            // Mover la posición X para la siguiente celda en la misma fila
            $this->ante_x += ANCHO_ROTULO_TABLA_ORDEN;
            $this->SetXY($this->ante_x, $this->ante_y);
            // Campo del cliente
            $this->SetFont('DejaVu', '', 8);
            $this->MultiCell(w: ANCHO_ROTULO_TABLA_ORDEN, h: ALTO_ROTULO_TABLA_ORDEN, txt: $valor, border: 1, align: 'C', fill: true);
            // Guardar posición X 
            $this->ante_x += ANCHO_ROTULO_TABLA_ORDEN;
            
            
        }

        // Formato es un diccionario
        function setFormato($formato)
        {
            // Si existe la clave font es un array de 3 elementos
            if(isset($formato['font']))
            {
                if(es_diccionario($formato['font']))
                    $this->SetFont($formato['font']['name'], $formato['font']['style'], $formato['font']['size']);
                else
                    $this->SetFont($formato['font'][0], $formato['font'][1], $formato['font'][2]);
            }

            // Si existe la clave text_color es un array de 3 elementos
            if(isset($formato['text_color']))
            {
                if(es_diccionario($formato['text_color']))
                    $this->SetTextColor($formato['text_color']['r'], $formato['text_color']['g'], $formato['text_color']['b']);
                else
                    $this->SetTextColor($formato['text_color'][0], $formato['text_color'][1], $formato['text_color'][2]);
            }

            // Si existe la clave fill_color es un array de 3 elementos
            if(isset($formato['fill_color']))
            {
                if(es_diccionario($formato['fill_color']))
                    $this->SetFillColor($formato['fill_color']['r'], $formato['fill_color']['g'], $formato['fill_color']['b']);
                else
                    $this->SetFillColor($formato['fill_color'][0], $formato['fill_color'][1], $formato['fill_color'][2]);
            }

        }

        // $contenido y $formato deben ser arrays de la misma longitud
        function AgregarFila($contenidos, $formatos)
        {
            // Si no son arrays
            if(!is_array($contenidos) || !is_array($formatos))
                throw new Exception('Los argumentos deben ser arrays');

            // Si no son del mismo tamaño
            if (count($contenidos) != count($formatos)) {
                throw new Exception('Los arrays deben ser de la misma longitud');            
            }

            // Primer paso: Obtener el valor más alto de la altura de las celdas
            $Max_Alto = 0;
            $Index_Max_Alto =0;

            for ($i = 0; $i < count($contenidos); $i++) 
            {
                $contenido = $contenidos[$i];
                $formato = $formatos[$i];

                $this->setFormato($formato);
                
                $ancho = $formato['w'];
                $alto = $formato['h'];
                $alto_font = $formato['font']['size'];
                $ancho_font = $this->GetStringWidth($contenido);
                $div = $ancho_font / $ancho;
                // Si el contenido es mayor que el ancho de la celda, se divide entre el ancho de la celda para obtener la altura
                if($div > (int)($div)) $div = (int)($div) +1; 
                else $div = (int)($div);
                // if($div > 2) $div += ($div - 1)-1;

                $alto_total_font = $div * $alto_font;
                if($alto_total_font > $alto) $alto = $alto_total_font;
                if($alto > $Max_Alto) 
                {
                    $Max_Alto = $alto;
                    $Index_Max_Alto = $i;
                }
            }

            $a_x= $this->GetX(); // Anterior X
            $a_y = $this->GetY(); // Anterior Y
            
            
            
            // Comprobación: Rebasa la hoja?
            if(($Max_Alto + $a_y) >= ($this->h-10)) 
            {
                // Añadir a consola el error            
                throw new Exception('Se ha sobrepasado la hoja');
            }

            // Segundo paso: Insertar celda por celda de la fila
            for ($i = 0; $i < count($contenidos); $i++) 
            {
                $this->SetXY($a_x, $a_y);
                $contenido = $contenidos[$i];
                $formato = $formatos[$i];
                $this->setFormato($formato);
                if($i <> $Index_Max_Alto) 
                    $this->MultiCell($formato['w'], $Max_Alto, $contenido, 1, $formato['align'], true);
                else
                    $this->MultiCell($formato['w'], $formato['h'], $contenido, 1, $formato['align'], true);
                
                $a_x += $formato['w'];
                
            }

            

        }

        function SaltosLinea(int $veces = 1)
        {
            $salto_linea = <<<EOT
            
            EOT;

            $cadena = "";

            for ($i = 0; $i < $veces; $i++)
            {
                $cadena .= "\n";
            }

            return $cadena;
        }

        // $contenido y $formato pueden ser arrays de diferente longitud
        function AgregarFila2($contenidos, $formatos)
        {
            // Formatos debe tener al menos un elemento
            if(count($formatos) < 1)
                throw new Exception('Deben existir al menos un formato');

            // Párrafo con saltos de línea
            $salto_linea = <<<EOT
            
            EOT;

            // Si no son arrays
            if(!is_array($contenidos) || !is_array($formatos))
                throw new Exception('Los argumentos deben ser arrays');

            // Si no son del mismo tamaño
            // if (count($contenidos) != count($formatos)) {
            //     throw new Exception('Los arrays deben ser de la misma longitud');            
            // }

            // Primer paso: Obtener el valor más alto de lineas de texto
            $Max_Alto = 0;
            $Max_Lines = 0;
            $Lineas = array();
            $Index_Max_Alto =0;

            for ($i = 0; $i < count($contenidos); $i++) 
            {
                $contenido = $contenidos[$i];
                if($contenido == "") $contenido = " ";
                
                if(count($formatos) > $i) 
                {
                    $formato = $formatos[$i];
                }

                $this->setFormato($formato);
                
                $ancho = $formato['w'];
                $alto = $formato['h'];
                $alto_font = $formato['font']['size'];
                $ancho_font = $this->GetStringWidth($contenido);
                $div = $ancho_font / $ancho;
                // Si el contenido es mayor que el ancho de la celda, se divide entre el ancho de la celda para obtener la altura
                if($div > (int)($div)) $div = (int)($div) +1; 
                else $div = (int)($div);
                $Lineas[$i] = $div;
                if($Max_Lines < $div) $Max_Lines = $div;
                // if($div > 2) $div += ($div - 1)-1;

                $alto_total_font = $div * $alto_font;
                if($alto_total_font > $alto) $alto = $alto_total_font;
                if($alto > $Max_Alto) 
                {
                    $Max_Alto = $alto;
                    $Index_Max_Alto = $i;
                }
            }

            $a_x= $this->GetX(); // Anterior X
            $a_y = $this->GetY(); // Anterior Y
            
            
            
            // Comprobación: Rebasa la hoja?
            if(($Max_Alto + $a_y) >= ($this->h-10)) 
            {
                // Añadir a consola el error            
                throw new Exception('Se ha sobrepasado la hoja');
            }

            // Segundo paso: Insertar celda por celda de la fila
            for ($i = 0; $i < count($contenidos); $i++) 
            {
                $diferencia_entre_max_lines = $Max_Lines - $Lineas[$i];

                $this->SetXY($a_x, $a_y);
                $contenido = $contenidos[$i];
                if (count($formatos) > $i)
                    $formato = $formatos[$i];

                $this->setFormato($formato);
                if ($diferencia_entre_max_lines > 0)
                    $contenido_preparado = $contenido . $this->SaltosLinea($diferencia_entre_max_lines +1 );
                else
                    $contenido_preparado = $contenido;
                $this->MultiCell($formato['w'], $formato['h'], $contenido_preparado . " ", 1, $formato['align'], true);
                
                $a_x += $formato['w'];
                
            }

            

        }


        function EncabezadoOrdenTrabajo()
        {
            $this->SetFillColor(20, 40, 125);
            $this->SetTextColor(255, 255, 255);
            $this->SetFont('DejaVu', 'B', 12);
            $this->Cell(0, 10, 'DATOS DE LA ORDEN DE TRABAJO', 1, 1, 'C', true);
        }

        function TablaOrdenTrabajo($cliente, $codCliente, $numConceptos, $idOrden, $representante, $medioPago, $monto)
        {

            $this->EncabezadoOrdenTrabajo();

            $formato_rotulo = [
                'font' => ['name' => 'DejaVu', 'style' => 'B', 'size' => 8],
                'align' => 'C', 'text_color'=> ['r' => 0, 'g' => 0, 'b' => 0], 'fill_color' => [230,240,255],
                'w' => ANCHO_ROTULO_TABLA_ORDEN, 'h' => ALTO_ROTULO_TABLA_ORDEN
                        ];

            $formato_valor = [
                'font' => ['name' => 'DejaVu', 'style' => '', 'size' => 8],
                'align' => 'C', 'text_color'=> ['r' => 0, 'g' => 0, 'b' => 0], 'fill_color' => ['r' => 230, 'g' => 240, 'b' => 255],
                'w' => ANCHO_ROTULO_TABLA_ORDEN, 'h' => ALTO_ROTULO_TABLA_ORDEN
                        ];

            $contenidos = [
                'CLIENTE', $cliente, 
                'COD ID CLIENTE', $codCliente,
                'NO. CONCEPTOS', $numConceptos
            ];
            
            $formatos = [
                $formato_rotulo, array_merge($formato_valor, ['w' => ANCHO_ROTULO_TABLA_ORDEN + 12]),
                $formato_rotulo, array_merge($formato_valor, ['w' => ANCHO_ROTULO_TABLA_ORDEN + 15]), 
                $formato_rotulo, array_merge($formato_valor, ['w' => ANCHO_ROTULO_TABLA_ORDEN + 1]), 
                
            ];

            $this->AgregarFila($contenidos, $formatos);

            $contenidos = [
                'ID ORDEN', $idOrden, 
                'FECHA', date('d-m-Y'),
                'HORA', date('H:i:s')
            ];

            $this->AgregarFila($contenidos, $formatos);

            $contenidos = [
                'REPRESENTANTE', $representante, 
                'MEDIO DE PAGO', $medioPago,
                'POR MONTO DE', $monto
            ];

            $this->agregarFila($contenidos, $formatos);
            
            $this->Ln(5);
        }

        function EncabezadoTablaServicios($ancho, $alto)
        {
            

            $this->SetFillColor(20, 40, 125);
            $this->SetTextColor(255, 255, 255);
            $this->SetFont('DejaVu', 'B', 12);
            $this->Cell(0, 10, 'DESGLOSE DE SERVICIOS ADQUIRIDOS', 1, 1, 'C', true);
            
            $formato_rotulo = [
                'font' => ['name' => 'DejaVu', 'style' => 'B', 'size' => 8],
                'align' => 'C', 'text_color'=> ['r' => 255, 'g' => 255, 'b' => 255], 'fill_color' => [ 21,96,130],
                'w' => $ancho, 'h' => $alto
                        ];

            $contenidos = [
                "SERVICIO", "DESCRIPCION", "PERIODO", "COSTO", "DESCUENTO", "TOTAL"
            ];

            $formatos = [
                $formato_rotulo, $formato_rotulo, $formato_rotulo, 
                $formato_rotulo, $formato_rotulo, $formato_rotulo
            ];

            try
            {

                $this->AgregarFila($contenidos, $formatos);
            } 
            catch (Exception $e)
            {

                throw new Exception($e->getMessage());
            }
        }

        function TablaServicios($servicios, $costos, $descuentos, $totales)
        {
            
            // Medidas de celdas de la tabla
            $ANCHO = 32.65; 
            $ALTO = 8;
            $this->EncabezadoTablaServicios($ANCHO, $ALTO);

            $formato_contenido = [
                'font' => ['name' => 'DejaVu', 'style' => '', 'size' => 8],
                'align' => 'C', 'text_color'=> [0,0,0], 'fill_color' => [192,230,245],
                'w' => $ANCHO, 'h' => $ALTO
                        ];


            // $formatos = array_fill(0,6, $formato_contenido);
            $formatos = [$formato_contenido]; 
                    
            foreach ($servicios as $servicio)
            {
                $contenidos = $servicio;
                try
                {
                    $this->agregarFila2($contenidos, $formatos);
                }
                catch (Exception $e)
                {
                    if ($e->getMessage() == 'Se ha sobrepasado la hoja')
                    {
                        $this->AddPage();
                        $this->EncabezadoTablaServicios($ANCHO, $ALTO);
                        $this->agregarFila2($contenidos, $formatos);
                    }
                }
            }

            // Agregar fila de subtotales o totales
            $contenidos = [
            "TOTALES", "", "", "{$costos}", "{$descuentos}", "{$totales}"
            ];

            $formato_contenido = [
                'font' => ['name' => 'DejaVu', 'style' => 'B', 'size' => 8],
                'align' => 'C', 'text_color'=> [0,0,0], 'fill_color' => [255,255,255],
                'w' => $ANCHO, 'h' => $ALTO
                        ];
            $formatos = array_fill(0,6, $formato_contenido);

            $this->agregarFila2($contenidos, $formatos);
            
            $this->Ln(5);
        }

        function ImprimirTotales($totales)
        {
            // Comprobar si queda espacio en la hoja
            if ($this->GetY() >= $this->PageBreakTrigger)
            {
                $this->AddPage();
            }

            $this->SetFont('DejaVu', 'B', 12);
            $this->Cell(178, 10, 'TOTAL:', 0, 0, 'R');
            $this->SetFont('DejaVu', '', 12);
            // Con borde inferior
            $this->Cell(18, 10, $totales, 0, 1, 'R');
        }

        function ImprimirContactos($contactos)
        {
            // $formato_url = "https://api.whatsapp.com/send/?phone=527712229085&text&type=phone_number&app_absent=0";

            // Comprobar si queda espacio en la hoja
            if ($this->GetY() >= $this->PageBreakTrigger)
            {
                $this->AddPage();
            }

            $this->SetFont('DejaVu', 'B', 12);
            $this->Cell(178, 10, 'CONTACTOS DE ATENCIÓN POR WHATSAPP:', 0, 1, 'L');
            
            foreach ($contactos as $i => $contacto)
            {

                $url = "https://api.whatsapp.com/send/?phone={$contacto}&text&type=phone_number&app_absent=0";
                $this->SetFont('DejaVu', 'I', 12);
                $this->SetTextColor(25,25,255);
                $this->Cell(18, 10, $i, 0, 1, 'L', 0, $url);

            }
            

        }

        
        function Footer()
        {
            $this->SetY(-1);
            $pre_alto = $this->GetY();
            $this->SetY(-15);
            $this->SetFont('','I',10);
            $alto = $this->GetY();
            $this->Image(LOGO2,65,$this->GetY(),10,10,'','http://www.fpdf.org');        
            $this->SetTextColor(128);
            $this->Cell(0, 10, SLOGAN, 0, 0, 'C'); 
            
            $this->Image(LOGO2,140,$this->GetY(),10,10,'','http://www.fpdf.org');        
            $this->Ln(1);
            $this->SetX(10);
            $this->SetFont('','B',6);
            $this->Cell(0, 10, 'Página '.$this->PageNo(),0, 1, 'L');
            
        }
    }

    function crear_comprobante($data)
    {
        // Crear nueva instancia de PDF
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        try {
            $pdf = new PDF(size: 'LETTER');
            $pdf->AddFont('DejaVu', '', 'DejaVuSansCondensed.ttf', true);
            $pdf->AddFont('DejaVu', 'B', 'DejaVuSansCondensed-Bold.ttf', true);
            $pdf->AddFont('DejaVu', 'I', 'DejaVuSansCondensed-Oblique.ttf', true);

            // Añadir nueva página
            $pdf->AddPage();

            // Título
            $pdf->TituloComprobante();

            // Tabla 1: Datos de la Orden de Trabajo
            $pdf->TablaOrdenTrabajo(
                'Tapicería y Cortinas el Danés',
                'COD001',
                '3',
                'ORD123',
                'Juan Pérez',
                'Tarjeta de Crédito',
                '$1000.00'
            );

            // Tabla 2: Desglose de Servicios
            $servicios = 
            [
                [
                'Difusión semanal de comercial',
                'Difusión por 7 días del comercial y/o anexos en más de 250 grupos (Facebook, WhatsApp, etc.)' ,
                '21/07/2024-27/07/2024',
                '$150.00',
                '$20.00',
                '$130.00'
                ],
                [
                'Difusión mensual de comercial',
                'Difusión por 30 días del comercial y/o anexos en más de 250 grupos (Facebook, WhatsApp, etc.)',        
                '21/07/2024-21/08/2024',
                '$600.00',
                '$50.00',
                '$550.00'
                ],
            ];


            $pdf->TablaServicios($servicios, $costos = '$750.00', $descuentos = '$70.00', $totales = '$680.00');

            $pdf->ImprimirTotales('$680.00');

            $pdf->ImprimirContactos(array("LINEA 1: +52 " . $data['telefono'] => '52' . $data['telefono'], "LINEA 2: +52 771-231-1116" => '527712311116'));

            $filename = isset($data['filename']) ? $data['filename'] : 'comprobante_detallado.pdf';

            $pdf->Output("F", $filename, true);

            return 'PDF creado con éxito en ' . $filename;
        } 
        catch (Exception $e) 
        {
            return 'Se produjo un error: ' . $e->getMessage();
        }
    }

    // crear_comprobante();
?>