<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Conexion;
use App\Services\Log;
use App\Services\ConsultaParametro;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Dompdf\Dompdf;
use Dompdf\Options;
use FPDF;
use App\Services\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use App\Services\ServiceEmail;
use DateTime;
use Smalot\PdfParser\Parser;
use Smalot\PdfParser\Document;
use phpseclib3\Net\SFTP;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TalentoHumanoController extends AbstractController
{
  private $log;
  private $cnn;
  private $prm;
  private $ruta;
  private $php_auth_user;
  private $php_auth_pw;
  private $http_client;
  private $filesystem;
  private $env;
  private $ip;
  private $excel;
  private $nickname;
  private $email;

  public function __construct(Log $log,Conexion $cnn,ConsultaParametro $prm,HttpClientInterface $http_client,Filesystem $filesystem, Excel $excel, ServiceEmail $email)
  {
    $this->log = $log;
    $this->cnn = $cnn;
    $this->prm = $prm;
    $this->ruta = $this->prm->parameter('kernel.project_dir');
    $this->log = new Log('Talento_humano_sgc', '', $this->ruta);
    $this->php_auth_user = null;
    $this->php_auth_pw = null;
    $this->http_client = $http_client;
    $this->filesystem = $filesystem;
    $this->excel = $excel;
    $this->env = $_ENV['APP_ENV'];
    $ip = "http://10.1.1.4:8094";
    $server_addr = getHostByName(php_uname('n')); //$_SERVER['SERVER_ADDR'];
    if($this->env == "dev") $ip = "http://$server_addr:8094";
    $this->ip = $ip;
    $this->email = $email;

    $auth = (!empty($_SERVER['HTTP_AUTHENTICATION'])) ? $_SERVER['HTTP_AUTHENTICATION'] : null;
    $this->log->logs("auth ".$auth);
    $jsonContent = json_decode($auth, true);
    $this->nickname = (!empty($jsonContent[0]['nickname'])) ? $jsonContent[0]['nickname'] : null;
    if (empty($this->nickname)) $this->nickname = (!empty($jsonContent['nickname'])) ? $jsonContent['nickname'] : null;
  }

  private function initialize()
  {
    if (empty($this->nickname))
    {
      $response = new JsonResponse();
      $response->setStatusCode(401);
      $respuesta["code"] = 401;
      $respuesta["datos"] = "Su sesión ha expirado";
      return $response->setContent(json_encode($respuesta));
    }
  }

  private function enviar_correo($email,$asunto,$info_cuerpo,$destinatarios, $titulo = "NOTIFICACION DE RESPUESTA SOLICITUD DE TALENTO HUMANO")
  {
    if($this->ip != "http://10.1.1.4:8094")
    {
      $asunto = "Pruebas -> ".$asunto;
      $destinatarios = null;
      $destinatarios[0] = "desarrollo2@consuerte.com.co";
    }
    $this->log->logs("********************Inicia private enviar_correo TH***********************");
    $cuerpo='<head>
                <meta name="viewport" content="width=device-width,initial-scale=1">
                <meta name="x-apple-disable-message-reformatting">
                <title></title>
                <style>
                    table,
                    td,
                    div,
                    h1,
                    p {
                        font-family: Arial, sans-serif;
                    }

                    @media screen and (max-width: 530px) {
                        .unsub {
                            display: block;
                            padding: 8px;
                            margin-top: 14px;
                            border-radius: 6px;
                            background-color: #555555;
                            text-decoration: none !important;
                            font-weight: bold;
                        }

                        .col-lge {
                            max-width: 100% !important;
                        }
                    }

                    @media screen and (min-width: 531px) {
                        .col-sml {
                            max-width: 27% !important;
                        }

                        .col-lge {
                            max-width: 73% !important;
                        }
                    }
                </style>
            </head>

            <body style="margin:0;padding:0;word-spacing:normal;background-color:#939297;">
                <div role="article" aria-roledescription="email" lang="en"
                    style="text-size-adjust:100%;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;background-color:#1086FF;">
                    <table role="presentation" style="width:100%;border:none;border-spacing:0;">
                        <tr>
                            <td align="center" style="padding:0;">
                                <table role="presentation"
                                    style="width:94%;max-width:600px;border:none;border-spacing:0;text-align:left;font-family:Arial,sans-serif;font-size:16px;line-height:22px;color:#363636;">
                                    <tr>
                                        <td style="padding:40px 30px 30px 30px;text-align:center;font-size:24px;font-weight:bold;">
                                            <a href="https://www.consuerte.com.co/" style="text-decoration:none;">
                                                <img src="https://www.consuerte.com.co/img/logo-consuerte-color.9ceacb2d.svg"
                                                    width="165" alt="Consuerte Villavicencio"
                                                    style="width:165px;max-width:80%;height:auto;border:none;text-decoration:none;color:#d20e2d;">
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:30px;background-color:#ffffff;">
                                            <h1
                                                style="margin-top:0;margin-bottom:16px;font-size:26px;line-height:32px;font-weight:bold;letter-spacing:-0.02em;">
                                                '.$titulo.'
                                            </h1>
                                            <p style="margin:0;">
                                                Cordial saludo,<br><br>'.$info_cuerpo.'
                                            </p>

                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:30px;background-color:#ffffff;">
                                            <p style="margin:0;">Cordialmente,
                                                <br><br><br>
                                                TALENTO HUMANO
                                                <br>
                                                acespedes@consuerte.com.co
                                                <br>
                                                ajquebradab@consuerte.com.co
                                            </p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td
                                            style="padding:30px;text-align:center;font-size:12px;background-color:#404040;color:#cccccc;">
                                            <p style="margin:0;font-size:14px;line-height:20px;">&reg; Consuerte Villavicencio -
                                                Meta<br>
                                                <a class="unsub" href="https://www.consuerte.com.co"
                                                    style="color:#cccccc;text-decoration:underline;">Dirección: Calle 15 N 40 - 01
                                                    Centro
                                                    Comercial
                                                    Primavera Urbana - Oficina 1001
                                                    Teléfono: (608) 670 98 98 - 320 831 42 93
                                                </a>
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>
            </body>

            </html>';
    // $respuesta = $email->email($asunto, $cuerpo, $destinatarios);
    $respuesta = $email->email(mb_convert_encoding($asunto, 'UTF-8', 'ISO-8859-1'), mb_convert_encoding($cuerpo, 'UTF-8', 'ISO-8859-1'), $destinatarios);
    $res = json_decode($respuesta, true);
    $info[0] = $res['code'];
    $info[1] = $res['message'];
    $this->log->logs("********************Fin private enviar_correo TH***********************");
    return $info;
  }

  private function generar_pdf_luto($fechaDoc,$familiar,$fechaLicencia,$fechaRegreso,$nombrePersona,$cargo,$destination,$fechaFinal,$ruta_firma)
  {
    $this->log->logs("********************Inicia private generar_pdf_luto TH***********************");
    $pdf = new FPDF('P', 'cm','letter');
    $pdf->useUnicode = true;

    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(50, 1, "Villavicencio, ".$fechaDoc."", 0, 1, 'L');

    $pdf->Ln(2);
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(50, 1, "Señor", 0, 1, 'L');

    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(50, 1, "JAIRO HERNAN PATIÑO OROZCO", 0, 1, 'L');

    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(50, 1, "Gerente", 0, 1, 'L');

    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(50, 1, "SEM S.A", 0, 1, 'L');

    $pdf->Ln(2);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(50, 1, "REF: SOLICITUD DE LICENCIA POR LUTO ", 0, 1, 'L');

    $pdf->Ln(2);
    $pdf->SetFont("Arial", '', 12);
    $pdf->SetTextColor(0,0,0);
    $pdf->Cell(50,0.6,"Por medio de la presente solicito licencia por luto debido a que mi ".$familiar." falleció el día ".$fechaLicencia."",0,1,'L');
    $pdf->SetTextColor(0,0,0);
    $pdf->Cell(50,0.6,"por lo tanto requiero desde el día ".$fechaLicencia." hasta el día ".$fechaFinal." y regresaría a mis labores",0,1,'L');
    $pdf->SetTextColor(0,0,0);
    $pdf->Cell(50,0.6,"el día ".$fechaRegreso.".",0,1,'L');

    $pdf->Ln(2);
    $pdf->SetFont("Arial",'', 12);
    $pdf->SetTextColor(0,0,0);
    $pdf->Cell(50,1,"Agradezco su colaboración.",0,1,'L');

    $pdf->Ln(1);
    $pdf->SetFont("Arial",'', 12);
    $pdf->SetTextColor(0,0,0);
    $pdf->Cell(50,1,"Atentamente,",0,1,'L');

    $pdf->Ln(4);
    $pdf->SetFont("Arial",'B', 12);
    $pdf->SetTextColor(0,0,0);
    $pdf->Cell(50,1,"_________________________________",0,1,'L');

    // $pdf->Image($ruta_firma, 1, 8, 0);
    // Agregar la imagen escalada al PDF
    $pdf->Image($ruta_firma, 2.5, 15.5, -180);

    $pdf->Cell(50,1,"".$nombrePersona.".",0,1,'L');
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont("Arial",'', 11);
    $pdf->Cell(50,0,"".$cargo."",0,1,'L');

    $pdf->Output('F', $destination);

    $respuesta["code"] = 1;
    $respuesta["datos"] = "PDF OK";
    $this->log->logs("********************Fin private generar_pdf_luto TH***********************");
    return json_encode($respuesta);
  }

  private function seleccionar_nombre_estado_tipo($n_estado,$tipo_solicitud,$solicitud_anulacion=0,$tipo=null)
  {
    switch ($n_estado)
    {
      case '0':
        $estado="Pendiente por aprobacion";
        if($tipo_solicitud=="12") $estado="Pendiente finalizar solicitud";
        break;
      case '1':
        $estado = ($solicitud_anulacion==1) ? "Pendiente por anulacion" : "Aceptado" ;
        break;
      case '2':
        $estado="Rechazado";
        break;
      case '3':
        $estado="Anulado";
        break;
      case '4':
        $estado="Vencido";
        break;
      case '5':
        $estado="Rechazado por documentacion";
        break;
      default:
        $estado = "Desconocido";
    }

    switch ($tipo_solicitud)
    {
      case '1':
        $tipo_s="Licencias no remuneradas";
        $causa = 213;
        break;
      case '2':
        $tipo_s="Dia de cumpleaños";
        $causa = 210;
        break;
      case '3':
        $tipo_s="Dia de la familia";
        $causa = 210;
        break;
      case '4':
        $tipo_s="Licencia de Luto";
        $causa = 210;
        break;
      case '5':
        $tipo_s="Enfermedad General";
        $causa = 200;
        break;
      case '6':
        $tipo_s="Accidente de trabajo";
        $causa = 205;
        break;
      case '7':
        $tipo_s="Accidente de transito";
        $causa = 200;
        break;
      case '8':
        $tipo_s="Licencia Maternidad";
        $causa = 211;
        break;
      case '9':
        $tipo_s="Licencia Paternidad";
        $causa = 212;
        break;
      case '10':
        $tipo_s="Licencia Remunerada";
        $causa = 210;
        break;
      case '11':
        $tipo_s="Dia no Laborado por Novedad";
        $causa = "";
        break;
      case '12':
        $tipo_s="Novedad Coordinador";
        $causa = "";
        break;
      case '13':
        $tipo_s="Vacaciones";
        $causa = "";
        break;
      default:
        $tipo_s = "Desconocido";
        $causa = ".";
    }

    $n_tipo_arr=array("N/A","ADMINISTRATIVO","VENDEDOR(A)");
    $n_tipo = null;
    if (!empty($tipo))
    {
      $n_tipo = $n_tipo_arr[$tipo];
    }

    $respuesta[0] = $estado;
    $respuesta[1] = $tipo_s;
    $respuesta[2] = $causa;
    $respuesta[3] = $n_tipo;
    return $respuesta;
  }

  private function array_solicitudes_excel($fechai,$fechaf,$tipo = 1,$tipo_usuario=null)
  {
    $this->log->logs("********************Inicia private array_solicitudes_excel TH***********************");
    $arr_cedulas = array();
    $vec_gamble = array();
    $estado = "";
    if ($tipo == 2) $estado = "and a.estado = 1 and a.tipo='$tipo_usuario' and a.tipo_solicitud < 11";

    $fechaObjeto = new DateTime($fechai);
    $fechaObjeto->modify("- 1 days");
    $fechai = $fechaObjeto->format("Y-m-d");

    /* $sql = "WITH temp AS (SELECT th1.id, substr((SELECT replace((SELECT json_each((SELECT json_array_elements(arr_informacion::json)
    FROM th_licencias_incapacidades th2 WHERE th2.id = th1.id limit 1)::json)
    LIMIT 1 OFFSET CASE WHEN th1.tipo_solicitud = 2 THEN 1 ELSE 2 END)::text, '\"', '')), 8, 10) AS fecha FROM th_licencias_incapacidades th1)
    SELECT a.id,a.tipo_solicitud,a.fecha_sys,a.estado,a.usuario,p.nombres||' '||p.apellido1 as n_usuario,
    CASE when a.tipo=1 then (select nombre from th_personal_administrativo_areas tpaa where estado = 0 and id = a.area)
    when a.tipo=2 then (select nombre from territorios t where codigo = a.area) end as n_area, a.tipo, a.area, a.arr_informacion,
    a.solicitud_anulacion, date(t.fecha) as fecha
    FROM temp t
    join th_licencias_incapacidades a on a.id = t.id
    join personas p on p.documento::text=substring(a.usuario,3)
    where date(t.fecha) between '$fechai' and '$fechaf' $estado order by a.id desc"; */
    $sql = "SELECT * FROM
      (WITH temp AS (SELECT th1.id, substr((SELECT replace((SELECT json_each((SELECT json_array_elements(arr_informacion::json)
      FROM th_licencias_incapacidades th2 WHERE th2.id = th1.id and th2.tipo_solicitud != 12 limit 1)::json)
      LIMIT 1 OFFSET CASE WHEN th1.tipo_solicitud = 2 THEN 1 ELSE 2 END)::text, '\"', '')), 8, 10) AS fecha FROM th_licencias_incapacidades th1)
      SELECT a.id,a.tipo_solicitud,a.fecha_sys,a.estado,a.usuario,p.nombres||' '||p.apellido1 as n_usuario,
      CASE when a.tipo=1 then (select nombre from th_personal_administrativo_areas tpaa where estado = 0 and id = a.area)
      when a.tipo=2 then (select nombre from territorios t where codigo = a.area) end as n_area, a.tipo, a.area, a.arr_informacion,
      a.solicitud_anulacion, date(t.fecha) as fecha
      FROM temp t
      join th_licencias_incapacidades a on a.id = t.id
      join personas p on p.documento::text=substring(a.usuario,3)
      where date(t.fecha) between '$fechai' and '$fechaf' $estado
      UNION ALL
      SELECT a.id, 12, a.fecha_sys, a.estado, a.usuario, concat(p.nombres,' ',p.apellido1), t.nombre, a.tipo, a.area, a.arr_informacion,
      a.solicitud_anulacion, date(a.fecha_sys)  FROM th_licencias_incapacidades a
      join personas p on p.documento::text=substring(a.usuario,3)
      JOIN (select id, nombre from th_personal_administrativo_areas tpaa where estado = 0 UNION ALL select codigo,nombre from territorios WHERE tpotrt_codigo = '15') t ON t.id = a.area
      WHERE a.tipo_solicitud = 12 AND date(a.fecha_sys) between '$fechai' and '$fechaf' $estado) x order by x.id DESC";

    $res_sql = $this->cnn->query('0', $sql);
    $aux1 = 0;
    if (count($res_sql) > 0)
    {
      foreach ($res_sql as $res)
      {//solo las incapacidades validan si trabajo el dia
        unset($r_metodo,$estado,$tipo_s,$causa,$n_tipo,$fecha_p);
        $fecha_p = trim($res['fecha']);

        // $this->log->logs("fecha_p $fecha_p fechai $fechai fechaf $fechaf snr: ".$this->solicitud_en_rango(trim($res['arr_informacion'])));
        if($fecha_p==$fechai && !$this->solicitud_en_rango(trim($res['arr_informacion']))) continue;
        else if($fecha_p==$fechaf && $this->solicitud_en_rango(trim($res['arr_informacion']))) continue;

        $r_metodo = $this->seleccionar_nombre_estado_tipo(trim($res['estado']),trim($res['tipo_solicitud']),trim($res['solicitud_anulacion']),trim($res['tipo']));
        $estado = $r_metodo[0];
        $tipo_s = utf8_encode($r_metodo[1]);
        $causa = $r_metodo[2];
        $n_tipo = $r_metodo[3];

        $vec_gamble[$aux1][0] = trim($res['id']);//id_visita
        $vec_gamble[$aux1][1] = $tipo_s;//tipo_solicitud
        $vec_gamble[$aux1][2] = trim($res['fecha_sys']);//fecha_sys
        $vec_gamble[$aux1][3] = $estado;//val_estado
        $vec_gamble[$aux1][4] = trim($res['usuario']);//usuario
        $vec_gamble[$aux1][5] = trim($res['n_usuario']);//n_usuario
        $vec_gamble[$aux1][6] = trim($res['n_area']);//n_area
        $vec_gamble[$aux1][7] = $n_tipo;//tipo
        $vec_gamble[$aux1][8] = trim($res['area']);//area
        $vec_gamble[$aux1][9] = trim($res['arr_informacion']);//arr_inf
        $vec_gamble[$aux1][10] = $causa;//causa
        $vec_gamble[$aux1][11] = trim($res['tipo_solicitud']);//solicitud
        $vec_gamble[$aux1][12] = trim(substr($res['usuario'], 2));//c_usuario
        $vec_gamble[$aux1][13] = trim($res['estado']);//n_estado

        $cedula = "'".trim(substr($res['usuario'], 2))."'";
        array_push($arr_cedulas, $cedula);
        $aux1++;
      }
    }
    $r[0] = $vec_gamble;
    $r[1] = $arr_cedulas;
    $this->log->logs("********************Fin private array_solicitudes_excel TH***********************");
    return $r;
  }

  private function solicitud_en_rango($arr_inf)
  {
    $a_json1=json_decode($arr_inf,true);
    foreach($a_json1 as $row)
    {
      $titulo=strtoupper(trim(utf8_decode($row['titulo'])));
      if ($titulo=='TRABAJO EL DIA DE INICIO DE LA LICENCIA?' || $titulo=='TRABAJO EL DIA DE INICIO DE LA INCAPACIDAD?')
        if(utf8_decode($row['informacion'])=="Si") return true;
    }
    return false;
  }

  private function validar_solicitudes_pendientes($fechai,$fechaf,$tipo_usuario)
  {
    $this->log->logs("********************Inicia private validar_solicitudes_pendientes TH***********************");
    $respuesta = array();
    $pendientes = 0;

    /* $fei_exp=explode("/",$fechai);
    $fef_exp=explode("/",$fechaf);

    $fechai=$fei_exp[2]."-".$fei_exp[0]."-".$fei_exp[1];
    $fechaf=$fef_exp[2]."-".$fef_exp[0]."-".$fef_exp[1]; */
    $fechaObjeto = new DateTime($fechai);
    $fechaObjeto->modify("- 1 days");
    $fechai = $fechaObjeto->format("Y-m-d");

    /* $sql = "select count(1) as cantidad, 'SOLICITUDES DE ANULACION PENDIENTES' as comentario, '1' as tipo_pendiente
    from th_licencias_incapacidades tli where estado = '1' and solicitud_anulacion = '1' and date(fecha_sys)>='$fechai' and date(fecha_sys)<='$fechaf'
    union all
    select count(1) as cantidad, 'AUSENTISMOS PENDIENTES' as comentario, '2' as tipo_pendiente
    from th_licencias_incapacidades tli where estado = '0' and tipo_solicitud  < '4' and date(fecha_sys)>='$fechai' and date(fecha_sys)<='$fechaf'
    union all
    select count(1) as cantidad, 'INCAPACIDADES PENDIENTES' as comentario, '3' as tipo_pendiente
    from th_licencias_incapacidades tli where estado = '0' and tipo_solicitud  > '3' and date(fecha_sys)>='$fechai' and date(fecha_sys)<='$fechaf'"; */
    $sql = "WITH temp AS (SELECT th1.id, substr((SELECT replace((SELECT json_each((SELECT json_array_elements(arr_informacion::json)
    FROM th_licencias_incapacidades th2 WHERE th2.id = th1.id limit 1)::json)
    LIMIT 1 OFFSET CASE WHEN th1.tipo_solicitud = 2 THEN 1 ELSE 2 END)::text, '\"', '')), 8, 10) AS fecha FROM th_licencias_incapacidades th1)
    SELECT th3.arr_informacion, th3.estado, th3.solicitud_anulacion, th3.tipo_solicitud, date(t.fecha) as fecha FROM temp t
    join th_licencias_incapacidades th3 on th3.id = t.id and
    ((th3.estado = '1' and th3.solicitud_anulacion = '1') or (th3.estado = '0' and th3.tipo_solicitud  < '4') or (th3.estado = '0' and th3.tipo_solicitud  > '3') and th3.tipo_solicitud < 11)
    and th3.tipo='$tipo_usuario'
    where date(t.fecha) between '$fechai' and '$fechaf'";

    $res_sql = $this->cnn->query('0', $sql);
    $cant1=0;
    $cant2=0;
    $cant3=0;
    if (count($res_sql) > 0)
    {
      foreach ($res_sql as $res)
      {
        $fecha_p = trim($res['fecha']);
        $estado = trim($res['estado']);
        $tipo_solicitud = trim($res['tipo_solicitud']);
        if($fecha_p==$fechai)
        {
          if($this->solicitud_en_rango(trim($res['arr_informacion'])))
          {
            if($estado == "1" && trim($res['solicitud_anulacion']) == "1") $cant1++;
            else if($estado == "0" && $tipo_solicitud < 4) $cant2++;
            else if($estado == "0" && $tipo_solicitud > 3) $cant3++;
          }
        }
        else if($fecha_p==$fechaf)
        {
          if(!$this->solicitud_en_rango(trim($res['arr_informacion'])))
          {
            if($estado == "1" && trim($res['solicitud_anulacion']) == "1") $cant1++;
            else if($estado == "0" && $tipo_solicitud < 4) $cant2++;
            else if($estado == "0" && $tipo_solicitud > 3) $cant3++;
          }
        }
        else
        {
          if($estado == "1" && trim($res['solicitud_anulacion']) == "1") $cant1++;
          else if($estado == "0" && $tipo_solicitud < 4) $cant2++;
          else if($estado == "0" && $tipo_solicitud > 3) $cant3++;
        }
      }
      $respuesta[0]["cantidad"] = $cant1;
      $respuesta[0]["comentario"] = "SOLICITUDES DE ANULACION PENDIENTES";
      $respuesta[0]["tipo_pendiente"] = "1";
      $respuesta[1]["cantidad"] = $cant2;
      $respuesta[1]["comentario"] = "AUSENTISMOS PENDIENTES";
      $respuesta[1]["tipo_pendiente"] = "2";
      $respuesta[2]["cantidad"] = $cant3;
      $respuesta[2]["comentario"] = "INCAPACIDADES PENDIENTES";
      $respuesta[2]["tipo_pendiente"] = "3";
      $pendientes = $cant1+$cant2+$cant3;
    }
    $r[0]=$pendientes;
    $r[1]=$respuesta;
    $this->log->logs("********************Fin private validar_solicitudes_pendientes TH***********************");
    return $r;
  }

  private function array_solicitudes_manager_excel_cedula($cedulas)
  {
    $this->log->logs("********************Inicia private array_solicitudes_manager_excel_cedula TH***********************");
    $vec_manager = array();
    $sql = "SELECT v.VINCEDULA, v.VINNOMBRE, n.NEMCCOSTO, n.NEMDESTINO, TO_CHAR(v.VINFECING, 'YYYY-MM-DD') as VINFECING
    from VINCULADO v, nmempleado n where v.VINCEDULA IN ($cedulas) AND v.VINCEDULA = n.NEMCEDULA";

    $res_sql = $this->cnn->query('5', $sql);
    $aux1 = 0;
    if (count($res_sql) > 0)
    {
      foreach ($res_sql as $res)
      {
        $vec_manager[$aux1][0] = trim($res['VINCEDULA']);
        $vec_manager[$aux1][1] = trim($res['VINNOMBRE']);
        $vec_manager[$aux1][2] = trim($res['NEMCCOSTO']);
        $vec_manager[$aux1][3] = trim($res['NEMDESTINO']);
        $vec_manager[$aux1][4] = trim($res['VINFECING']);

        $aux1++;
      }
    }
    $this->log->logs("********************Fin private array_solicitudes_manager_excel_cedula TH***********************");
    return $vec_manager;
  }

  private function array_solicitudes_gamble_excel_pdv($pdvs)
  {
    $this->log->logs("********************Inicia private array_solicitudes_gamble_excel_pdv TH***********************");
    $vec_gamble = array();
    $sql = "SELECT codigo, nombre FROM territorios where codigo IN ($pdvs)";

    $res_sql = $this->cnn->query('2', $sql);
    $aux1 = 0;
    if (count($res_sql) > 0)
    {
      foreach ($res_sql as $res)
      {
        $vec_gamble[$aux1][0] = trim($res['CODIGO']);
        $vec_gamble[$aux1][1] = ($res['NOMBRE']);
        $aux1++;
      }
    }
    $this->log->logs("********************Fin private array_solicitudes_gamble_excel_pdv TH***********************");
    return $vec_gamble;
  }

  private function cargar_tabla_ppal_th($operadorid,$fechai,$fechaf,$tipo_visita,$estado_solicitud,$cc_usuario)
  {
    $this->log->logs("********************Inicia private cargar_tabla_ppal_th***********************");
    $respuesta = array();
    $novedades_coordinador = array();
    $asesoras_coordinador = array();
    $contenido='';
    $i=0;
    $opc=$operadorid;
    $option="";

    if($opc=="1")//busqueda por fechas
      $option=" WHERE date(a.fecha_sys)>='$fechai' and date(a.fecha_sys)<='$fechaf'";
    else if($opc=="2")//tipo solicitud
      $option=" WHERE a.tipo_solicitud='$tipo_visita'";
    else if($opc=="5")//estado solicitud
    {
      $option = ($estado_solicitud == "'6'") ? " WHERE a.solicitud_anulacion='1' and a.estado='1'" : " WHERE a.estado=$estado_solicitud and a.solicitud_anulacion!='1'";
    }
    else if($opc=="6")//usuario
      $option=" WHERE substring(a.usuario,3)='$cc_usuario'";

    /* $sql="select a.id,a.tipo_solicitud,date(a.fecha_sys) as fecha_sys,a.estado,a.usuario ||' - '||p.nombres||' '||p.apellido1 as usuario,
    CASE when a.tipo=1 then (select nombre from th_personal_administrativo_areas tpaa where estado = 0 and id = a.area)
    when a.tipo=2 then (select nombre from territorios t where codigo = a.area) end as area, a.solicitud_anulacion
    from th_licencias_incapacidades a, personas p
    where p.documento::text=substring(a.usuario,3) $option
    order by a.id desc"; */
    $sql="SELECT a.id,a.tipo_solicitud,date(a.fecha_sys) as fecha_sys,a.estado,a.usuario ||' - '||p.nombres||' '||p.apellido1 as usuario,
    COALESCE(tpaa.nombre, t.nombre, 'COMERCIAL') AS area, a.solicitud_anulacion, a.arr_informacion
    from th_licencias_incapacidades a
    join personas p ON p.documento::text = substring(a.usuario, 3)
    LEFT JOIN th_personal_administrativo_areas tpaa ON tpaa.estado = 0 AND tpaa.id = a.area AND a.tipo = 1
    LEFT JOIN territorios t ON t.codigo = a.area AND a.tipo = 2 $option
    order by a.id desc";

    $res_sql = $this->cnn->query('0', $sql);
    if (count($res_sql) > 0)
    {
      foreach ($res_sql as $res)
      {
        unset($tipo_s,$estado,$id,$r_metodo);

        $r_metodo = $this->seleccionar_nombre_estado_tipo(trim($res['estado']),trim($res['tipo_solicitud']),trim($res['solicitud_anulacion']));
        $estado = $r_metodo[0];
        $tipo_s = utf8_encode($r_metodo[1]);
        $id = trim($res['id']);

        array_push($respuesta, array("ID" => $id,"SOLICITUD" => $tipo_s, "FECHA" => trim($res['fecha_sys']), "ESTADO" => $estado,
        "USUARIO" => $res['usuario'], "AREA" => $res['area'],
        "OPCIONES" => "<center>
          <button  type=button id=btn_".$i." class='btn btn-primary' onclick='editar($id,1);' ><img src='images/move-down.svg' class='bi bi-pencil' width='12'></button>
          <span id=msm_camp_".$i." ></span>
        </center>"));

        $i++;
        if(trim($res['tipo_solicitud'])=="12")
        {
          $asesora = $this->buscar_elemento_json(trim($res['arr_informacion']), "ASESORA", "informacion");
          if(!empty($asesora))
          {
            $asesoras_coordinador[] = $asesora;
            $novedades_coordinador[] = array("id"=>$id, "asesora"=>$asesora);
          }
        }
      }
    }

    if(!empty($asesoras_coordinador))
    {
      $cedulas_separadas_por_comas = "'" . implode("','", $asesoras_coordinador) . "'";
      $sql2 = "SELECT VINNOMBRE, VINCEDULA FROM VINCULADO WHERE VINCEDULA in ($cedulas_separadas_por_comas)";
      $res_sql2=$this->cnn->query('5', $sql2);

      foreach ($novedades_coordinador as &$registro)
      {
        $cedula = $registro['asesora'];
        foreach($res_sql2 as $res2)
        {
          if ($cedula == trim($res2['VINCEDULA']))
          {
            $registro['asesora'] = " | " . $cedula . " - " . trim($res2['VINNOMBRE']);
            break;
          }
        }
      }
      unset($registro);

      foreach ($respuesta as &$registro)
      {
        $id = $registro['ID'];
        foreach ($novedades_coordinador as $novedad)
        {
          if ($id == $novedad['id'])
          {
            $registro['USUARIO'] = $registro['USUARIO'].$novedad['asesora'];
            break;
          }
        }
      }
    }

    $this->log->logs("********************Fin private cargar_tabla_ppal_th***********************");
    return $contenido.json_encode($respuesta);
  }

  private function buscar_elemento_json($json, $titulo, $tipo_dato)
  {
    $a_json2 = json_decode($json, true);
    foreach ($a_json2 as $row)
    {
      if(strtoupper(trim(utf8_decode($row['titulo'])))==$titulo &&
      array_key_exists($tipo_dato, $row) && !empty($row[$tipo_dato]))
        return $row[$tipo_dato];
    }
    return "";
  }

  private function actualizar_estado_solicitudes($estado_th,$id,$arr_informacion,$email,$fecha,$dias,$trabajo_el_dia,$comentario_th,$labora_festivos)
  {
    $this->log->logs("********************Inicia private actualizar_estado_solicitudes***********************");
    $sql="update th_licencias_incapacidades set estado='$estado_th', arr_informacion='$arr_informacion', area_res=1,
    fecha_mod = now(), usuario_mod='$this->nickname', obs_rta='$comentario_th'
    where id = '$id' returning id";
    $res_sql = $this->cnn->query('0', $sql);

    if (count($res_sql) > 0)
    {
      $info_solicitud = $this->obtener_solicitud_x_id($id);
      $permisos = ["","una LICENCIA NO REMUNERADA","un PERMISO DE CUMPLEAÑOS","un PERMISO DE DIA DE LA FAMILIA","una LICENCIA DE LUTO",
          "una INCAPACIDAD POR ENFERMEDAD GENERAL","una INCAPACIDAD POR ACCIDENTE DE TRABAJO",
          "una INCAPACIDAD POR ACCIDENTE DE TRANSITO","una INCAPACIDAD POR MATERNIDAD","una LICENCIA POR PATERNIDAD","una LICENCIA REMUNERADA","un DIA NO LABORADO POR NOVEDAD","","las VACACIONES"];
      $nombre_tipo_solicitud = $permisos[$info_solicitud["tipo_solicitud"]];
      $asunto = "Notificacion SEM Mobile";
      $getUsuario = substr($info_solicitud["usuario"], 2);
      $getNombre_u = $info_solicitud["n_usuario"];
      $fechaObjeto = DateTime::createFromFormat("Y-m-d H:i:s.u", $info_solicitud["fecha_sys"]);
      $getFecha_solicitud = $fechaObjeto->format("d-m-Y");

      $sql_manager = "SELECT VINEMAIL from VINCULADO where VINCEDULA='$getUsuario'";
      $result_manager = $this->cnn->query('5', $sql_manager);
      $destinatarios[0] = "";
      if ((count($result_manager) > 0))
      {
        $destinatarios[0] = $result_manager[0]['VINEMAIL'];
      }

      $dias_c = $dias;
      if($info_solicitud["tipo_solicitud"] == 4)
        $dias_c = 5;

      $n_tipo_pro = "solicito";
      $n_tipo_pro2 = "solicitados";
      $tipo_per = "permiso";
      if($info_solicitud["tipo_solicitud"] > 4 && $info_solicitud["tipo_solicitud"]!=10)
        $tipo_per = "incapacidad";
      else if($info_solicitud["tipo_solicitud"]==10 || $info_solicitud["tipo_solicitud"]==4)
        $tipo_per = "licencia";

      if($info_solicitud["tipo_solicitud"] >= 4 && $info_solicitud["tipo_solicitud"] <= 10)
      {
        $n_tipo_pro = "radico";
        $n_tipo_pro2 = "radicados";
      }
      $comentario_th = mb_convert_encoding($comentario_th, 'ISO-8859-1', 'UTF-8');
      switch ($estado_th)
      {
        case '1':
          if($trabajo_el_dia)
          {
            $fechaObjeto = new DateTime($fecha);
            $fechaObjeto->modify("+ 1 days");
            $fecha = $fechaObjeto->format("d-m-Y");
            $dias--;
            $dias_c = $dias;
          }
          $fechaObjeto = new DateTime($fecha);
          $fechaObjeto->modify("+".($dias-1)." days");
          $fecha_final_permiso = $fechaObjeto->format("d-m-Y");
          $fechaObjeto = new DateTime($fecha);
          $fechaObjeto->modify("+".$dias." days");
          $fecha_ingreso = $fechaObjeto->format("d-m-Y");

          if($labora_festivos == "1")
          {
            $festivos = array();
            $trabaja_festivos = true;
            $anio_act = date("Y");
            $anio_siguiente = date("Y", strtotime("+1 year"));
            $fechai_fes="01-01-".$anio_act;
            $fechaf_fes="31-12-".$anio_siguiente;
            $sql_fest = "SELECT date(fecha_festivo) as fest FROM tabla_festivos WHERE date(fecha_festivo) BETWEEN '$fechai_fes' AND '$fechaf_fes'";
            $res_festivos = $this->cnn->query('0', $sql_fest);
            foreach ($res_festivos as $row) array_push($festivos, $row['fest']);
            do
            {
              $resultado = $this->validarFecha($fecha_ingreso,$festivos);
              $this->log->logs("resultado",array($resultado));
              $fecha_ingreso = $resultado[0];
              $encontrado = $resultado[1];
            } while ($encontrado);
          }

          $informacion = "Usted $n_tipo_pro $nombre_tipo_solicitud. <br><br>Señor(a) usuario $getNombre_u identificado con CC $getUsuario".
          ". <br><br>Se le aprobaron $dias_c dia(s) de $tipo_per $n_tipo_pro2 el dia $getFecha_solicitud. ".
          "La fecha de inicio de $tipo_per es el $fecha hasta el $fecha_final_permiso, su fecha de ingreso a laborar es el $fecha_ingreso".
          ". <br><br>Su $tipo_per fue ACEPTADA por el area de Talento Humano.".
          "<br><br>Comentario realizado por Talento Humano: $comentario_th.";
          break;
        case '2':
          $informacion = "Usted $n_tipo_pro $nombre_tipo_solicitud. <br><br>Señor(a) usuario $getNombre_u identificado con CC $getUsuario.".
          "<br><br>Usted $n_tipo_pro $dias_c dia(s) $tipo_per el dia $getFecha_solicitud. ".
          "<br><br>Su $tipo_per fue RECHAZADA por el area de Talento Humano.".
          "<br><br>Comentario realizado por Talento Humano: $comentario_th.";
          break;
      }

      $res_correo = $this->enviar_correo($email,$asunto,$informacion,$destinatarios);
      $this->log->logs("r correo",$res_correo);
      $this->log->logs("********************Fin2 private actualizar_estado_solicitudes***********************");

      if($info_solicitud["tipo_solicitud"] == 4 && $estado_th == "1")
      {
        $sql_doc = "SELECT gd.id, g.nombre, g.prefijo_radicado FROM gabinetes_documentos gd
        JOIN gabinetes g ON g.id = gd.id_gabinete AND g.estado = '0'
        WHERE gd.id_gabinete = '6' AND gd.nombre = 'LICENCIA DE LUTO' AND gd.estado = '0'";
        $result_doc = $this->cnn->query('14', $sql_doc);
        if (count($result_doc) <= 0)
        {
          $res[0] = 1;
          $res[1] = "Estado de la solicitud actualizado, pero no se guardo en el SGD";
          return $res;
        }
        $id_doc = $result_doc[0]['id'];
        $nombre_gab = $result_doc[0]['nombre'];
        $prefijo_radicado = $result_doc[0]['prefijo_radicado'];
        $destination_path = $this->ruta . "/public/uploads/talento_humano/soportes/" . $this->env."/";
        $destination_path2 = $this->ruta . "/public/uploads/talento_humano/archivos/" . $this->env."/";
        // $destination_path = $this->ip . "/uploads/talento_humano/soportes/" . $this->env."/";
        $soportes = Array();
        $cont_i=0;
        if ($arr_informacion == null || empty($arr_informacion))
        {
          $res[0] = 1;
          $res[1] = "Estado de la solicitud actualizado, pero no se guardo en el SGD2";
          return $res;
        }

        if (!file_exists($destination_path."th_carta_luto_$id.pdf"))
        {
          $this->log->logs("No existe la carta");
          $this->generar_carta_luto($id);
        }

        $a_json2 = json_decode($arr_informacion, true);
        foreach ($a_json2 as $row)
        {
          if(array_key_exists('adjunto', $row) && !empty($row['adjunto']))
          {
            $soportes[$cont_i] = $row['adjunto'];
            $cont_i++;
          }
        }
        $paginas = 0;
        $soportes[$cont_i] = "th_carta_luto_$id.pdf";
        foreach ($soportes as $nombre)
        {
          $paginas += $this->numeroPaginasPdf($destination_path.$nombre);
        }

        if($paginas==0)
        {
          $res[0] = 1;
          $res[1] = "Estado de la solicitud actualizado, pero no se guardo en el SGD3";
          return $res;
        }

        $ruta_py = $this->ruta.'/src/Services/';
        $archivo = 'unir_pdfs.py';
        $n_archivo = "temporal_$id"."_".date("Y_m_d_H_i_s_u").".pdf";
        //$output = exec('python ' . $ruta_py . $archivo . ' \'' . json_encode($soportes) . '\' '.$destination_path. ' '.$n_archivo);
        $output = array();
        $error = "";
        $command = 'python3.8 ' . $ruta_py . $archivo . ' \'' . json_encode($soportes) . '\' ' . $destination_path . ' ' . $n_archivo;
        exec($command . ' 2>&1', $output, $returnCode);
        //$this->log->logs("output $output python " . $ruta_py . $archivo . ' \'' . json_encode($soportes) . '\' '.$destination_path. ' '.$n_archivo);

        if ($returnCode !== 0)
        {
          $error = implode("\n", $output);
          $this->log->logs("output ",array($error));
          $res[0] = 1;
          $res[1] = "Estado de la solicitud actualizado, pero no se guardo en el SGD10";
          return $res;
        }

        $this->log->logs("output ".$output[0]);
        if ($output[0] != 1)
        {
          $res[0] = 1;
          $res[1] = "Estado de la solicitud actualizado, pero no se guardo en el SGD5";
          return $res;
        }

        $usuario_rad = substr($this->nickname,2);
        $sql_insert_1 = "INSERT INTO $nombre_gab (usuario_rad,id_doc,id_proveedor,num_folios,estado,fecha_radicado,fecha_digitalizacion,prioridad,fecha_documento)
        VALUES ('$usuario_rad','$id_doc','$getUsuario','$paginas','0',now(),now(),'1','$fecha') returning id";
        $result_insert = $this->cnn->query('14', $sql_insert_1);
        if (count($result_insert) <= 0)
        {
          $res[0] = 1;
          $res[1] = "Estado de la solicitud actualizado, pero no se guardo en el SGD6";
          return $res;
        }
        $id_rad = $result_insert[0]['id'];
        $radicado = $prefijo_radicado."-".$id_rad;
        $this->log->logs("radicado $radicado de la solicitud $id");

        $sql_ruta = "select ruta_gabinetes from conf";
        $result_ruta = $this->cnn->query('14', $sql_ruta);
        if (count($result_ruta) <= 0)
        {
          $res[0] = 1;
          $res[1] = "Estado de la solicitud actualizado, pero no se guardo en el SGD7";
          return $res;
        }
        $ruta = $result_ruta[0]['ruta_gabinetes'];
        $partes = explode('/', $ruta);
        $partesDeseadas = array_slice($partes, 4); // Obtener las partes a partir del í­ndice 3
        $ruta_gabinetes = "http://10.1.1.2/".implode('/', $partesDeseadas);
        $this->log->logs("ruta_gabinetes $ruta_gabinetes");

        $ch = curl_init($ruta_gabinetes);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->log->logs("response",array($response));
        if ($httpCode != 200)
        {
          $res[0] = 1;
          $res[1] = "Estado de la solicitud actualizado, pero no se guardo en el SGD8";
          return $res;
        }

        $res_copia = $this->copiar_pdf($ruta."/".$nombre_gab,$id_rad,$destination_path2.$n_archivo);
        $this->log->logs("res_copia",array($res_copia));
        if ($res_copia["status"] != "0")
        {
          $res[0] = 1;
          $res[1] = "Estado de la solicitud actualizado, pero no se guardo en el SGD9";
          return $res;
        }
        $hash = $res_copia["has_file"];

        $sql_update_r = "UPDATE $nombre_gab SET radicado='$radicado', estado='4', hash = '$hash' WHERE id='$id_rad' returning id";
        $result_update = $this->cnn->query('14', $sql_update_r);
        if (count($result_update) <= 0)
        {
          $res[0] = 1;
          $res[1] = "Estado de la solicitud actualizado, pero no se guardo en el SGD";
          return $res;
        }

        $this->log->logs("unlink $destination_path2 $n_archivo");
        unlink($destination_path2.$n_archivo); // Elimina el archivo
      }

      $res[0] = 1;
      $res[1] = "Estado de la solicitud actualizado";
      return $res;
    }
    $res[0] = 0;
    $res[1] = "No se pudo actualizar, intente nuevamente";
    $this->log->logs("********************Fin private actualizar_estado_solicitudes***********************");
    return $res;
  }

  private function validarFecha($fecha,$festivos)
  {
    $calendar = new DateTime($fecha);
    if ($calendar->format('N') == 7)
    {
      $calendar->modify('+1 day');
      $fechaResultado = $calendar->format('d-m-Y');
      return array($fechaResultado, true);
    }

    foreach ($festivos as $fechaArray)
    {
      $fechaArray = new DateTime($fechaArray);
      if ($fechaArray->getTimestamp() === $calendar->getTimestamp())
      {
        $calendar->modify('+1 day');
        $fechaResultado = $calendar->format('d-m-Y');
        return array($fechaResultado, true);
      }
    }
    return array($fecha, false);
  }

  private function copiar_pdf($ruta_gabiente,$id_rad,$archivo_pdf)
  {
    $ftp_server = '10.1.1.2';
    $ftp_username = 'root';
    $ftp_password = '3spi4lidoso';
    $ftp_directory = $ruta_gabiente ."/". $id_rad;

    $sftp = new SFTP($ftp_server);
    if (!$sftp->login($ftp_username, $ftp_password))
    {
      $msm = "No se establecio el inicio de sesion";
      $status = "1";
      return array("status" => $status, "msm" => $msm);
    }

    if (!$sftp->mkdir($ftp_directory))
    {
      $msm = "No se pudo Crear la carpeta del Radicado " . $ruta_gabiente . "/" . $id_rad;
      $status = "1";
      return array("status" => $status, "msm" => $msm);
    }

    $archivo_pdf_n = $ftp_directory . "/" . $id_rad . ".pdf";
    if (!$sftp->put($archivo_pdf_n, $archivo_pdf, SFTP::SOURCE_LOCAL_FILE))
    {
      $msm = "No se puede copiar el archivo  $archivo_pdf a $archivo_pdf_n";
      $status = "1";
      return array("status" => $status, "msm" => $msm);
    }

    $remote_contents = $sftp->read($archivo_pdf_n);
    if ($remote_contents === false)
    {
      $msm = "No se puede leer el archivo $archivo_pdf_n";
      $status = "1";
      return array("status" => $status, "msm" => $msm);
    }

    $has_file = hash('sha256', $remote_contents);
    $sftp->disconnect();

    $status = "0";
    return array("status" => $status, "has_file" => $has_file);
  }

  private function numeroPaginasPdf($archivoPDF)
  {
    if (!file_exists($archivoPDF))
    {
      $this->log->logs("$archivoPDF ERROR no existe");
      return 0;
    }

      if(is_readable($archivoPDF))
      {
        $parser = new Parser();
        $pdf = $parser->parseFile($archivoPDF);
        $pages = $pdf->getPages();
        $pageCount = count($pages);
        return $pageCount;
      }
      else
      {
        $this->log->logs("$archivoPDF ERROR no readable");
      return 0;
    }
  }

  private function obtener_solicitud_x_id($id_visita)
  {
    $this->log->logs("********************Inicia private obtener_solicitud_x_id***********************");
    $resp[0] = 0;
    $sql_valida_tipo = "SELECT tipo FROM th_licencias_incapacidades tli WHERE id = $id_visita";
    $res_valida_tipo = $this->cnn->query('0', $sql_valida_tipo);
    if (count($res_valida_tipo) <= 0)
    {
      $resp[1] = "No se encontro la solicitud, intente nuevamente";
      return $resp;
    }

    $sql_visita="SELECT a.id,a.tipo_solicitud,a.fecha_sys,a.estado,a.usuario,p.nombres||' '||p.apellido1 as n_usuario, COALESCE(t.nombre, '') AS n_area,
    a.tipo, a.area, a.arr_informacion, a.obs_rta, a.solicitud_anulacion, a.comentario_sol_anulacion, 1 as labora_festivos
    from th_licencias_incapacidades a
    LEFT JOIN territorios t ON t.codigo = a.area
    JOIN personas p ON p.documento::text=substring(a.usuario,3)
    where a.id = $id_visita AND a.tipo = '2'";

    if(trim($res_valida_tipo[0]['tipo'])== "1")
    {
      $sql_visita="SELECT a.id,a.tipo_solicitud,a.fecha_sys,a.estado,a.usuario,p.nombres||' '||p.apellido1 as n_usuario,
      COALESCE(tpaa.nombre, 'COMERCIAL') as n_area, a.tipo, a.area, a.arr_informacion,
      a.obs_rta, a.solicitud_anulacion, a.comentario_sol_anulacion, tc.labora_festivos
      from th_licencias_incapacidades a
      JOIN personas p ON p.documento::text=substring(a.usuario,3)
      JOIN th_personal_administrativo tpa ON tpa.cedula = p.documento and tpa.estado='0'
      JOIN th_personal_administrativo_asignaciones tpaa2 ON tpaa2.id_per_adm_usuario_encargado = tpa.id AND tpaa2.estado ='0'
      JOIN th_cargos tc ON tc.id = tpaa2.id_per_adm_cargo AND tc.estado ='0'
      LEFT JOIN th_personal_administrativo_areas tpaa ON tpaa.id = a.area
      where a.id = $id_visita AND a.tipo = '1'";
    }
    $res = $this->cnn->query('0', $sql_visita);

    if (count($res) <= 0)
    {
      $resp[1] = "No se pudo consultar, intente nuevamente";
      return $resp;
    }
    $r_metodo = $this->seleccionar_nombre_estado_tipo(trim($res[0]['estado']),trim($res[0]['tipo_solicitud']),trim($res[0]['solicitud_anulacion']),trim($res[0]['tipo']));

    $solicitudes_LR = array();
    if(trim($res[0]['tipo_solicitud'])== "10")
    {
      $anio = date("Y");
      $fechai="01-01-".$anio;
      $fechaf="31-12-".$anio;
      $usu_db = trim($res[0]['usuario']);
      $sql_total_lr = "SELECT CASE estado WHEN 0 THEN 'LICENCIAS REMUNERADAS PENDIENTES' ELSE 'LICENCIAS REMUNERADAS ACEPTADAS' END AS estado, count(*) AS cantidad, arr_informacion, estado AS estado2
      FROM th_licencias_incapacidades WHERE usuario = '$usu_db' and ((date(fecha_sys) BETWEEN '$fechai' AND '$fechaf' AND estado = '1') OR estado = '0')
      AND tipo_solicitud='10' GROUP BY estado, arr_informacion, estado";
      $res_lr = $this->cnn->query('0', $sql_total_lr);
      if (count($res_lr) > 0)
      {
        foreach ($res_lr as $row)
        {
          $fecha = "";
          $dias = "";
          $a_json1=json_decode($row['arr_informacion'],true);
          foreach($a_json1 as $row2)
          {
            switch (utf8_decode(strtoupper($row2['titulo'])))
            {
              case 'DIAS DE LICENCIA':
                $dias = trim($row2['informacion']);
                break;
              case 'FECHA DE INICIO DE LA LICENCIA':
                $fecha = trim($row2['fecha']);
                break;
            }
          }
          array_push($solicitudes_LR, array("estado" => $row['estado'],"cantidad" => $row['cantidad'],"dias" => $dias,"fecha" => $fecha,"estado2" => $row['estado2']));
        }
      }
    }

    $resp[0] = 1;
    $resp["code"] = 1;
    $resp["id_visita"] = trim($res[0]['id']);
    $resp["tipo_solicitud"] = trim($res[0]['tipo_solicitud']);
    $resp["fecha_sys"] = $res[0]['fecha_sys'];
    $resp["val_estado"] = trim($res[0]['estado']);
    $resp["usuario"] = trim($res[0]['usuario']);
    $resp["n_usuario"] = $res[0]['n_usuario'];
    $resp["n_area"] = $res[0]['n_area'];
    $resp["tipo"] = trim($res[0]['tipo']);
    $resp["area"] = trim($res[0]['area']);
    $resp["arr_inf"] = $res[0]['arr_informacion'];
    $resp["estado"] = $r_metodo[0];
    $resp["tipo_s"] = utf8_encode($r_metodo[1]);
    $resp["n_tipo"] = $r_metodo[3];
    $resp["obs_rta"] = $res[0]['obs_rta'];
    $resp["solicitud_anulacion"] = $res[0]['solicitud_anulacion'];
    $resp["comentario_sol_anulacion"] = $res[0]['comentario_sol_anulacion'];
    $resp["labora_festivos"] = $res[0]['labora_festivos'];
    $resp["solicitudes_LR"] = $solicitudes_LR;
    $this->log->logs("********************Fin private obtener_solicitud_x_id***********************");
    return $resp;
  }

  private function array_codigos_enfermedades($codigo_enfermedad)
  {
    $this->log->logs("********************Inicia private array_codigos_enfermedades***********************");
    $array = array();
    $sql = "SELECT codigo, descripcion FROM th_codigos_enfermedades";
    $res_sql = $this->cnn->query('0', $sql);
    if (count($res_sql) > 0)
      foreach ($res_sql as $res)
        $array[] = array("codigo"=>trim($res['codigo']), "nombre"=>$res['descripcion']);
    $this->log->logs("********************Fin private array_codigos_enfermedades***********************");
    return $array;
  }

  private function analisis_solicitud_anulacion($usuario,$arr_inf)
  {
    $this->log->logs("********************Inicia private analisis_solicitud_anulacion***********************");

    $info_analisis = null;$fecini=null;$fecfin=null;$dias=null;
    $a_json1=json_decode($arr_inf,true);
    foreach($a_json1 as $row)
    {
      $info = "";
      if (array_key_exists('adjunto', $row) && !empty(utf8_decode($row['adjunto'])))
      {
        $info = utf8_decode($row['adjunto']);
      }
      elseif (!empty(utf8_decode($row['descripcion'])))
      {
        $info = utf8_decode($row['descripcion']);
      }
      elseif (!empty(utf8_decode($row['fecha'])))
      {
        $info = utf8_decode($row['fecha']);
      }
      elseif (!empty(utf8_decode($row['informacion'])))
      {
        $info = utf8_decode($row['informacion']);
      }
      $titulo = strtoupper(trim(utf8_decode($row['titulo'])));
      if ($titulo=='FECHA DE NACIMIENTO DEL BEBE' || $titulo=='FECHA DE INICIO DE LA INCAPACIDAD' ||
          $titulo=='FECHA DE INICIO DE LA LICENCIA' || $titulo=='FECHA DE INICIO DEL PERMISO' || $titulo=='DIA DEL PERMISO')
      {
        $info = date("Y-m-d", strtotime($info));
        $fecini = $info;
      }
      elseif($titulo=='DIAS DE INCAPACIDAD' || $titulo=='CANTIDAD DE DIAS DE PERMISO' || $titulo=='DIAS DE LICENCIA')
      {
        $dias = $info;
      }
    }

    $fecfin = date('Y-m-d', strtotime(date("Y-m-d",strtotime($fecini." + ".($dias-1)." days"))));
    $this->log->logs("dias $dias fecini $fecini fecfin $fecfin");

    for ($i=0; $i < $dias; $i++)
    {
      $info_analisis[$i]["valor"]="0";
      $info_analisis[$i]["valor2"]="0";
      $info_analisis[$i]["fecha"]=date('Y-m-d', strtotime(date("Y-m-d",strtotime($fecini." + $i days"))));
      $info_analisis[$i]["estado"]="0";
    }

    $sql="SELECT sum(r.valor) as valor,sum(r.valor2) as valor2,r.fecha,
    case when sum(r.valor)>=100000 then 1 when sum(r.valor)!=0 then 1 else 0 end as estado from
    (select prs_documento,sum(totalpagado) as valor,0 as valor2,fecha from formularios
    where fecha>=to_date('$fecini', 'YYYY-MM-DD') and fecha<=to_date('$fecfin', 'YYYY-MM-DD')
    and codigo_tipojuego='1' and dat_dto_codla_elaboracion_para='17' and prs_documento = '$usuario'
    group by prs_documento,fecha
    union all
    select prs_documento,sum(totalpagado) as valor,0 as valor2,fecha from hist_formularios
    where fecha>=to_date('$fecini', 'YYYY-MM-DD') and fecha<=to_date('$fecfin', 'YYYY-MM-DD')
    and codigo_tipojuego='1' and dat_dto_codla_elaboracion_para='17' and prs_documento = '$usuario'
    group by prs_documento,fecha
    union all
    select persona as prs_documento,0 as valor, sum(ventabruta) as valor2,fecha as fecha
    from v_totalventasnegocio
    where fecha>=to_date('$fecini', 'YYYY-MM-DD') and fecha<=to_date('$fecfin', 'YYYY-MM-DD')
    and servicio in ('751','780','1003','7562','7563','7565','7566') and persona = '$usuario'
    group by persona,fecha
    union all
    select persona as prs_documento,0 as valor, sum(abs(ventabruta)) as valor2,fecha as fecha
    from v_totalventasnegocio
    where fecha>=to_date('$fecini', 'YYYY-MM-DD') and fecha<=to_date('$fecfin', 'YYYY-MM-DD')
    and servicio in ('97','98','99','100') and ventabruta!=0 and persona = '$usuario'
    group by persona,fecha) r
    group by r.prs_documento,r.fecha
    order by prs_documento,fecha";
    $res_sql = $this->cnn->query('2', $sql);

    $this->log->logs("sql $sql");
    $this->log->logs("info_analisis",$info_analisis);

    if (count($res_sql) > 0)
    {
      foreach ($res_sql as $res)
      {
        unset($valor,$valor2,$fecha,$estado);
        $valor = trim($res['VALOR']);
        $valor2 = trim($res['VALOR2']);
        $fecha = DateTime::createFromFormat('d/m/Y',substr(trim($res['FECHA']), 0, 10))->format('Y-m-d');
        $estado = trim($res['ESTADO']);

        foreach ($info_analisis as &$analisis)
        {
          if ($analisis["fecha"] == $fecha)
          {
            $analisis["valor"] = $valor;
            $analisis["valor2"] = $valor2;
            $analisis["estado"] = $estado;
          }
        }
      }
    }
    $this->log->logs("info_analisis2",$info_analisis);
    $this->log->logs("********************Fin private analisis_solicitud_anulacion***********************");
    return $info_analisis;
  }

  private function analisis_doble_turno($arr_inf, $fecha_sys)
  {
    $this->log->logs("********************Inicia private analisis_doble_turno***********************");
    $info_analisis=null;$asesora=null;$pdv_doble=null;$pdv=null;$turno=null;$mismo_pdv="No";$fecha=date("Y-m-d", strtotime($fecha_sys));

    $a_json1=json_decode($arr_inf,true);
    foreach($a_json1 as $row)
    {
      switch (utf8_decode(strtoupper($row['titulo'])))
      {
        case 'ASESORA':
          $asesora = trim($row['informacion']);
          break;
        case 'PDV':
          $pdv_doble =  trim($row['informacion']);
          break;
        case 'TURNO':
          $turno = strtoupper(trim($row['informacion']));
          break;
        case 'MISMO PDV?':
          $mismo_pdv = trim($row['informacion']);
          break;
        case 'DIA DEL DOBLE TURNO':
          $fecha = date("Y-m-d", strtotime(trim($row['fecha'])));
          break;
      }
    }

    if($mismo_pdv=="Si")
    {
      $turno2 = "T";
      if($turno == "TARDE") $turno2 = "M";
      $pdv = $pdv_doble." - ".$turno2;
      $pdv_doble .= " - ".substr($turno, 0, 1);
    }
    else
    {
      $sql_pdv = "SELECT hraprs_ubcneg_trtrio_codigo as pdv FROM controlhorariopersonas c
      WHERE cal_dia=to_date('$fecha', 'YYYY-MM-DD') AND hraprs_contvta_prs_documento = '$asesora' AND hraprs_ubcneg_trtrio_codigo != '$pdv_doble'";
      $res_pdv = $this->cnn->query('2', $sql_pdv);
      if (!count($res_pdv) > 0)
      {
        $info_analisis['code'] = '0';
        $info_analisis['mensaje'] = 'NO SE ENCONTRO EL OTRO TURNO';
        $this->log->logs("********************Inicia private analisis_doble_turno1***********************");
        return $info_analisis;
      }
      $pdv = $res_pdv[0]['PDV'];
    }

    for ($i=0; $i < 2; $i++)
    {
      $info_analisis[$i]["valor"]="0";
      $info_analisis[$i]["valor2"]="0";
      $info_analisis[$i]["fecha"]=$fecha;
      $info_analisis[$i]["estado"]="0";
      if($i==0) $info_analisis[$i]["pdv"]=$pdv;
      else $info_analisis[$i]["pdv"]=$pdv_doble;
    }

    if($mismo_pdv=="Si")
    {
      if($turno != "TARDE")
      {
        $aux = $pdv;
        $pdv = $pdv_doble;
        $pdv_doble = $aux;
      }
      $sql=$this->sql_val_doble_turno_mismo_pdv("CV".$asesora,$fecha,"00:00:01","13:59:59",$pdv);
      $sql.=" UNION ALL ";
      $sql.=$this->sql_val_doble_turno_mismo_pdv("CV".$asesora,$fecha,"14:00:00","23:59:59",utf8_decode($pdv_doble));
    }
    else
    {
      $sql="SELECT sum(r.valor) as valor,sum(r.valor2) as valor2,r.fecha,
      case when (sum(r.valor)>=100000 and sum(r.valor2)!=0) then 1 else 0 end as estado, r.pdv from
      (SELECT prs_documento,sum(totalpagado) as valor,0 as valor2,fecha, UBCNEG_TRTRIO_CODIGO AS pdv from formularios
      where fecha=to_date('$fecha', 'YYYY-MM-DD')
      and codigo_tipojuego='1' and dat_dto_codla_elaboracion_para='17' and prs_documento = '$asesora'
      group by prs_documento,fecha,UBCNEG_TRTRIO_CODIGO
      union all
      SELECT prs_documento,sum(totalpagado) as valor,0 as valor2,fecha, UBCNEG_TRTRIO_CODIGO AS pdv from hist_formularios
      where fecha=to_date('$fecha', 'YYYY-MM-DD')
      and codigo_tipojuego='1' and dat_dto_codla_elaboracion_para='17' and prs_documento = '$asesora'
      group by prs_documento,fecha,UBCNEG_TRTRIO_CODIGO
      union all
      SELECT persona as prs_documento,0 as valor, sum(ventabruta) as valor2,fecha, sucursal as pdv from v_totalventasnegocio
      where fecha=to_date('$fecha', 'YYYY-MM-DD')
      and servicio in ('751','780','1003','7562','7563','7565','7566') and persona = '$asesora'
      group by persona,fecha,sucursal
      union all
      SELECT persona as prs_documento,0 as valor, sum(abs(ventabruta)) as valor2,fecha, sucursal as pdv from v_totalventasnegocio
      where fecha=to_date('$fecha', 'YYYY-MM-DD')
      and servicio in ('97','98','99','100') and ventabruta!=0 and persona = '$asesora'
      group by persona,fecha,sucursal) r
      group by r.prs_documento,r.fecha,r.pdv
      order by prs_documento,fecha,pdv";
    }

    $res_sql = $this->cnn->query('2', $sql);

    if (count($res_sql) <= 0)
    {
      $info_analisis['code'] = '0';
      $info_analisis['mensaje'] = 'NO SE ENCONTRO VENTAS DEL USUARIO';
      $this->log->logs("********************Inicia private analisis_doble_turno2***********************");
      return $info_analisis;
    }

    foreach ($res_sql as $res)
    {
      foreach ($info_analisis as &$analisis)
      {
        if(substr($analisis["pdv"], -1) == "M") $analisis["pdv"] = utf8_encode($analisis["pdv"]."añana");
        else if(substr($analisis["pdv"], -1) == "T") $analisis["pdv"] = $analisis["pdv"]."arde";
        if (substr($analisis["pdv"], 0, 8) == trim($res['PDV']))
        {
          $analisis["valor"] = trim($res['VALOR']);
          $analisis["valor2"] = trim($res['VALOR2']);
          $analisis["estado"] = trim($res['ESTADO']);
        }
      }
    }
    $analisis['code'] = '1';
    $analisis['mensaje'] = 'VENTAS ENCONTRADAS';
    $this->log->logs("info_analisis2",$info_analisis);
    $this->log->logs("********************Fin private analisis_doble_turno***********************");
    return $info_analisis;
  }

  private function sql_val_doble_turno_mismo_pdv($login,$fecha,$horai,$horaf,$jornada)
  {
    /* $sql = "SELECT sum(r2.valor) AS valor, sum(r2.valor2) AS valor2, r2.fecha, case when (sum(r2.valor)>=100000 and sum(r2.valor2)!=0) then 1 else 0 end as estado, '$jornada' AS pdv from
    (
    select TO_NUMBER(r.PRS_DOCUMENTO) AS PRS_DOCUMENTO,sum(r.ventabruta) as valor, 0 AS valor2, to_char(r.fecha, 'YYYY-MM-DD') AS fecha  from
    (select ltrim(login,'CVP') AS prs_documento,sum(f.totalpagado) as ventabruta,f.fecha from formularios f
    inner join
    (select case when d.servicio_codigo is null then 73 else d.servicio_codigo end as servicio,  t.tipojuego from detalleincentivos d
    inner join
    (select p.codigo_tipojuego as tipojuego from equivalenciasproductos e inner join productos p on e.productogamble=p.codigo group by p.codigo_tipojuego) t
    on d.codigo_planincentivos='99' and d.codigo_tipojuego=t.tipojuego and fechafinal is null
    group by case when d.servicio_codigo is null then 73 else d.servicio_codigo end,  t.tipojuego
    union all
    select servicio_codigo, tipojuego from gamble_otrosservicios where tipojuego not in ('49','52')
    and servicio_codigo not in ('151','751','753','7562')
    group by servicio_codigo, tipojuego) s on f.codigo_tipojuego=s.tipojuego
    where f.fecha=to_date('$fecha', 'YYYY-MM-DD') AND TO_DATE(f.hora, 'HH24:MI:SS') BETWEEN TO_TIMESTAMP('$horai', 'HH24:MI:SS') AND TO_TIMESTAMP('$horaf', 'HH24:MI:SS')
    and f.dat_dto_codla_elaboracion_para='17' and login='$login' GROUP BY login,f.fecha
    union all
    select ltrim(login,'CVP') AS prs_documento,sum(f.totalpagado) as ventabruta, f.fecha from hist_formularios f
    inner join
    (select case when d.servicio_codigo is null then 73 else d.servicio_codigo end as servicio,  t.tipojuego from detalleincentivos d
    inner join
    (select p.codigo_tipojuego as tipojuego from equivalenciasproductos e inner join productos p
    on e.productogamble=p.codigo group by p.codigo_tipojuego) t on d.codigo_planincentivos='99' and d.codigo_tipojuego=t.tipojuego and fechafinal is null
    group by case when d.servicio_codigo is null then 73 else d.servicio_codigo end,  t.tipojuego
    union all
    select servicio_codigo, tipojuego from gamble_otrosservicios where tipojuego not in ('49','52')
    and servicio_codigo not in ('151','751','753','7562') group by servicio_codigo, tipojuego) s
    on f.codigo_tipojuego=s.tipojuego
    where f.fecha=to_date('$fecha', 'YYYY-MM-DD') AND TO_DATE(f.hora, 'HH24:MI:SS') BETWEEN TO_TIMESTAMP('$horai', 'HH24:MI:SS') AND TO_TIMESTAMP('$horaf', 'HH24:MI:SS')
    and f.dat_dto_codla_elaboracion_para='17' and login='$login' GROUP BY login,f.fecha) r GROUP BY r.PRS_DOCUMENTO,r.fecha
    union ALL
    select a.prs_documento,0 AS valor,sum(a.valor) as valor2, to_char(a.fecha, 'YYYY-MM-DD') AS fecha  from
    (select d.servicio_codigo,d.prs_documento,sum(d.valor) as valor, d.fechaventa AS fecha from detallevtasotrosproductos  d
    left join consolidadoventaservicios c
    on d.prs_documento=c.prs_documento and d.servicio_codigo=c.servicio_codigo and d.fechaventa=c.fechaventa and d.nit=c.nit
    where d.fechaventa=to_date('$fecha', 'YYYY-MM-DD') AND TO_DATE(d.horaventa, 'HH24:MI:SS') BETWEEN TO_TIMESTAMP('$horai', 'HH24:MI:SS') AND TO_TIMESTAMP('$horaf', 'HH24:MI:SS')
    and d.prs_documento=cast(ltrim('$login','CVP') as numeric) group by d.servicio_codigo,d.prs_documento,d.fechaventa) a
    left join
    (select case when d.servicio_codigo is null then 73 else d.servicio_codigo end as servicio from detalleincentivos d
    inner join
    (select p.codigo_tipojuego as tipojuego from equivalenciasproductos e inner join productos p on e.productogamble=p.codigo group by p.codigo_tipojuego) t
    on d.codigo_planincentivos='99' and d.codigo_tipojuego=t.tipojuego and d.fechafinal is NULL group by case when d.servicio_codigo is null then 73 else d.servicio_codigo end
    union all
    select distinct servicio_codigo from gamble_otrosservicios where tipojuego not in ('49','52')
    and servicio_codigo not in ('151','751','753','7562')) s
    on a.servicio_codigo=s.servicio where s.servicio is NULL group by a.prs_documento, a.fecha
    ) r2 GROUP BY r2.prs_documento, r2.fecha"; */

    $sql = "SELECT sum(r2.valor) AS valor, sum(r2.valor2) AS valor2, r2.fecha, case when (sum(r2.valor)>=100000 and sum(r2.valor2)!=0) then 1 else 0 end as estado, '$jornada' AS pdv from
    (
    select TO_NUMBER(r.PRS_DOCUMENTO) AS PRS_DOCUMENTO,sum(r.ventabruta) as valor, 0 AS valor2, to_char(r.fecha, 'YYYY-MM-DD') AS fecha  from
    (select ltrim(login,'CVP') AS prs_documento,sum(f.totalpagado) as ventabruta,f.fecha from formularios f
    inner join
    (select case when d.servicio_codigo is null then 73 else d.servicio_codigo end as servicio,  t.tipojuego from detalleincentivos d
    inner join
    (select p.codigo_tipojuego as tipojuego from equivalenciasproductos e inner join productos p on e.productogamble=p.codigo group by p.codigo_tipojuego) t
    on d.codigo_planincentivos='99' and d.codigo_tipojuego=t.tipojuego and fechafinal is null
    group by case when d.servicio_codigo is null then 73 else d.servicio_codigo end,  t.tipojuego
    union all
    select servicio_codigo, tipojuego from gamble_otrosservicios where tipojuego not in ('49','52')
    and servicio_codigo not in ('151','751','753','7562')
    group by servicio_codigo, tipojuego) s on f.codigo_tipojuego=s.tipojuego
    where f.fecha=to_date('$fecha', 'YYYY-MM-DD') AND TO_DATE(f.hora, 'HH24:MI:SS') BETWEEN TO_TIMESTAMP('$horai', 'HH24:MI:SS') AND TO_TIMESTAMP('$horaf', 'HH24:MI:SS')
    and f.dat_dto_codla_elaboracion_para='17' and login='$login' GROUP BY login,f.fecha
    union all
    select ltrim(login,'CVP') AS prs_documento,sum(f.totalpagado) as ventabruta, f.fecha from hist_formularios f
    inner join
    (select case when d.servicio_codigo is null then 73 else d.servicio_codigo end as servicio,  t.tipojuego from detalleincentivos d
    inner join
    (select p.codigo_tipojuego as tipojuego from equivalenciasproductos e inner join productos p
    on e.productogamble=p.codigo group by p.codigo_tipojuego) t on d.codigo_planincentivos='99' and d.codigo_tipojuego=t.tipojuego and fechafinal is null
    group by case when d.servicio_codigo is null then 73 else d.servicio_codigo end,  t.tipojuego
    union all
    select servicio_codigo, tipojuego from gamble_otrosservicios where tipojuego not in ('49','52')
    and servicio_codigo not in ('151','751','753','7562') group by servicio_codigo, tipojuego) s
    on f.codigo_tipojuego=s.tipojuego
    where f.fecha=to_date('$fecha', 'YYYY-MM-DD') AND TO_DATE(f.hora, 'HH24:MI:SS') BETWEEN TO_TIMESTAMP('$horai', 'HH24:MI:SS') AND TO_TIMESTAMP('$horaf', 'HH24:MI:SS')
    and f.dat_dto_codla_elaboracion_para='17' and login='$login' GROUP BY login,f.fecha) r GROUP BY r.PRS_DOCUMENTO,r.fecha
    union ALL
    select a.prs_documento,0 AS valor,sum(a.valor) as valor2, to_char(a.fecha, 'YYYY-MM-DD') AS fecha  from
    (select d.servicio_codigo,d.prs_documento,sum(d.valor) as valor, d.fechaventa AS fecha from detallevtasotrosproductos  d
    left join consolidadoventaservicios c
    on d.prs_documento=c.prs_documento and d.servicio_codigo=c.servicio_codigo and d.fechaventa=c.fechaventa and d.nit=c.nit
    and d.sucursal=c.sucursal
    where d.fechaventa=to_date('$fecha', 'YYYY-MM-DD') AND TO_DATE(d.horaventa, 'HH24:MI:SS') BETWEEN TO_TIMESTAMP('$horai', 'HH24:MI:SS') AND TO_TIMESTAMP('$horaf', 'HH24:MI:SS')
    and d.prs_documento=cast(ltrim('$login','CVP') as numeric) group by d.servicio_codigo,d.prs_documento,d.fechaventa) a
    left join
    (select case when d.servicio_codigo is null then 73 else d.servicio_codigo end as servicio from detalleincentivos d
    inner join
    (select p.codigo_tipojuego as tipojuego from equivalenciasproductos e inner join productos p on e.productogamble=p.codigo group by p.codigo_tipojuego) t
    on d.codigo_planincentivos='99' and d.codigo_tipojuego=t.tipojuego and d.fechafinal is NULL group by case when d.servicio_codigo is null then 73 else d.servicio_codigo end
    union all
    select distinct servicio_codigo from gamble_otrosservicios where tipojuego not in ('49','52')
    and servicio_codigo not in ('151','751','753','7562')) s
    on a.servicio_codigo=s.servicio where s.servicio is NULL group by a.prs_documento, a.fecha
    ) r2 GROUP BY r2.prs_documento, r2.fecha";
    return $sql;
  }

  private function anular_dia_especifico($id,$arr_inf,$parametro)
  {
    $this->log->logs("********************Inicia private anular_dia_especifico***********************");
    $info_analisis = null;$fecini=null;$fecfin=null;$dias=null;$id_row=null;
    $a_json1=json_decode($arr_inf,true);
    $this->log->logs("a_json1 ",array($a_json1));
    foreach($a_json1 as &$row)
    {
      $info = "";
      if (array_key_exists('adjunto', $row) && !empty(utf8_decode($row['adjunto'])))
      {
        $info = utf8_decode($row['adjunto']);
        $id_row = "adjunto";
      }
      elseif (!empty(utf8_decode($row['descripcion'])))
      {
        $info = utf8_decode($row['descripcion']);
        $id_row = "descripcion";
      }
      elseif (!empty(utf8_decode($row['fecha'])))
      {
        $info = utf8_decode($row['fecha']);
        $id_row = "fecha";
      }
      elseif (!empty(utf8_decode($row['informacion'])))
      {
        $info = utf8_decode($row['informacion']);
        $id_row = "informacion";
      }
      $titulo = strtoupper(trim(utf8_decode($row['titulo'])));
      if ($titulo=='FECHA DE NACIMIENTO DEL BEBE' || $titulo=='FECHA DE INICIO DE LA INCAPACIDAD' ||
          $titulo=='FECHA DE INICIO DE LA LICENCIA' || $titulo=='FECHA DE INICIO DEL PERMISO' || $titulo=='DIA DEL PERMISO')
      {
        $info = date("Y-m-d", strtotime($info));
        if ($info == $parametro)
          $row[$id_row] = date('d-m-Y', strtotime(date("Y-m-d",strtotime($info." + 1 days"))));
      }
      elseif($titulo=='DIAS DE INCAPACIDAD' || $titulo=='CANTIDAD DE DIAS DE PERMISO' || $titulo=='DIAS DE LICENCIA')
      {
        $row[$id_row] = strval($info-1);
      }
    }

    $this->log->logs("a_json2 ",array($a_json1));
    $arr_inf = json_encode($a_json1);

    $sql="UPDATE th_licencias_incapacidades set solicitud_anulacion = '2', fecha_mod=now(),
    usuario_mod='$this->nickname', obs_anula='Se anula dia $parametro por solicitud del usuario',
    obs_rta = 'Se anula dia $parametro por solicitud del usuario', arr_informacion = '$arr_inf' where id = '$id' returning id";
    $res = $this->cnn->query('0', $sql);

    if (count($res) <= 0)
    {
      $respuesta["code"] = 0;
      return $respuesta;
    }

    $respuesta["code"] = 1;
    $this->log->logs("********************Fin private anular_dia_especifico***********************");
    return $respuesta;
  }

  private function registrar_cargos_asignaciones($arrDatos,$id_area)
  {
    $this->log->logs("********************Inicia private registrar_cargos_asignaciones***********************");
    $arrFallas = array();
    foreach ($arrDatos as $item)
    {
      unset($cargo,$nivel,$cedula,$id_usu_enc,$id_cargo);
      $cargo = $item['cargo'];
      // $nivel = $item['nivel'];
      $cedula = $item['cedula'];
      $id_cargo = $item['id_cargo'];

      $sql_personal="SELECT id FROM th_personal_administrativo WHERE cedula = '$cedula'";
      $res_sql_personal = $this->cnn->query('0', $sql_personal);

      if (count($res_sql_personal) <= 0)
      {
        $nuevoObjeto = array("tipo_err" => "La cedula $cedula no esta registrada, no se hace el registro del cargo $cargo");
        array_push($arrFallas, $nuevoObjeto);
        continue;
      }
      $id_usu_enc = $res_sql_personal[0]["id"];

      /* $sql_insert_cargos = "INSERT INTO th_cargos (nombre, id_per_adm_area, nivel) VALUES ('$cargo', $id_area, '$nivel') RETURNING id";
      $res_sql_insert_cargos = $this->cnn->query('0', $sql_insert_cargos);
      if (!(count($res_sql_insert_cargos) > 0))
      {
        $nuevoObjeto = array("tipo_err" => "No se pudo hacer el registro del cargo $cargo con nivel $nivel");
        array_push($arrFallas, $nuevoObjeto);
        continue;
      }
      $id_cargo = $res_sql_insert_cargos[0]["id"]; */

      $sql_asignacion="SELECT * FROM TH_PERSONAL_ADMINISTRATIVO_ASIGNACIONES WHERE estado=0  and ID_PER_ADM_USUARIO_ENCARGADO='$id_usu_enc'";
      $res_sql_asignacion = $this->cnn->query('0', $sql_asignacion);

      if (count($res_sql_asignacion) > 0)
      {
        $nuevoObjeto = array("tipo_err" => "La cedula $cedula ya tiene un cargo asignado, no se hace el registro del usuario con el $cargo");
        array_push($arrFallas, $nuevoObjeto);
        continue;
      }

      $sql_insert_encargado = "INSERT INTO TH_PERSONAL_ADMINISTRATIVO_ASIGNACIONES (id_per_adm_usuario_encargado,id_per_adm_cargo) VALUES ('$id_usu_enc', '$id_cargo') RETURNING id";
      $res_sql_insert_encargado = $this->cnn->query('0', $sql_insert_encargado);
      if (count($res_sql_insert_encargado) <= 0)
      {
        $nuevoObjeto = array("tipo_err" => "No se pudo asignar el cargo $cargo al usuario $cedula");
        array_push($arrFallas, $nuevoObjeto);
        continue;
      }
    }
    $this->log->logs("********************Fin private registrar_cargos_asignaciones***********************");
    return $arrFallas;
  }

  private function buscar_elemento(&$array, $id, $info)
  {
    foreach ($array as &$cargo)
      if ($cargo['id'] == $id)
        $cargo['nombres_cargos'] = $info;
  }

  private function array_solicitudes_excel_asesoras_sin_asignacion()
  {
    $this->log->logs("********************Inicia private array_solicitudes_excel_asesoras_sin_asignacion TH***********************");
    $vec_gamble = array();
    $arr_cedulas = array();
    $arr_pdv = array();

    $sql = "SELECT a.id,a.tipo_solicitud,a.fecha_sys,a.estado,a.usuario,p.nombres||' '||p.apellido1 as n_usuario,
    CASE when a.tipo=1 then (select nombre from th_personal_administrativo_areas tpaa where estado = 0 and id = a.area)
    when a.tipo=2 then (select nombre from territorios t where codigo = a.area) end as n_area, a.tipo, a.area, a.arr_informacion
    FROM th_licencias_incapacidades a
    join personas p on p.documento::text=substring(a.usuario,3)
    where a.estado = '0' AND a.tipo_solicitud = '12' order by a.id desc";

    $res_sql = $this->cnn->query('0', $sql);
    $aux1 = 0;
    if (count($res_sql) > 0)
    {
      foreach ($res_sql as $res)
      {
        unset($r_metodo,$estado,$tipo_s,$causa,$n_tipo,$fecha_p);

        $r_metodo = $this->seleccionar_nombre_estado_tipo(trim($res['estado']),trim($res['tipo_solicitud']),0,trim($res['tipo']));
        $estado = $r_metodo[0];
        $tipo_s = utf8_encode($r_metodo[1]);
        $causa = $r_metodo[2];
        $n_tipo = $r_metodo[3];

        $vec_gamble[$aux1][0] = trim($res['id']);//id_visita
        $vec_gamble[$aux1][1] = $tipo_s;//tipo_solicitud
        $vec_gamble[$aux1][2] = trim($res['fecha_sys']);//fecha_sys
        $vec_gamble[$aux1][3] = $estado;//val_estado
        $vec_gamble[$aux1][4] = trim($res['usuario']);//usuario
        $vec_gamble[$aux1][5] = trim($res['n_usuario']);//n_usuario
        $vec_gamble[$aux1][6] = trim($res['n_area']);//n_area
        $vec_gamble[$aux1][7] = $n_tipo;//tipo
        $vec_gamble[$aux1][8] = trim($res['area']);//area
        $vec_gamble[$aux1][9] = trim($res['arr_informacion']);//arr_inf
        $vec_gamble[$aux1][10] = $causa;//causa
        $vec_gamble[$aux1][11] = trim($res['tipo_solicitud']);//solicitud
        $vec_gamble[$aux1][12] = trim(substr($res['usuario'], 2));//c_usuario
        $vec_gamble[$aux1][13] = trim($res['estado']);//n_estado
        $vec_gamble[$aux1][14] = "";
        $vec_gamble[$aux1][16] = "";
        $vec_gamble[$aux1][18] = "";

        $a_json1=json_decode($vec_gamble[$aux1][9],true);
        foreach($a_json1 as $row)
        {
          switch (utf8_decode(strtoupper($row['titulo'])))
          {
            case 'ASESORA':
              $vec_gamble[$aux1][14] = trim($row['informacion']);
              $cedula = "'".$vec_gamble[$aux1][14]."'";
              array_push($arr_cedulas, $cedula);
              break;
              case 'PDV';
              $vec_gamble[$aux1][16] =  trim($row['informacion']);
              $pdv = "'".$vec_gamble[$aux1][16]."'";
              array_push($arr_pdv, $pdv);
              break;
            case 'TURNO';
              $vec_gamble[$aux1][18] = trim($row['informacion']);
              break;
          }
        }
        $aux1++;
      }
    }
    $r[0] = $vec_gamble;
    $r[1] = $arr_cedulas;
    $r[2] = $arr_pdv;
    $this->log->logs("********************Fin private array_solicitudes_excel_asesoras_sin_asignacion TH***********************");
    return $r;
  }

  private function generar_carta_luto($id)
  {
    $this->log->logs("********************Inicia generar_carta_luto TH***********************");
    $fechaLicencia="";$dias="";$familiar="";

    $meses = array("enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre");
    $fechaDoc = (date("d")." de ".$meses[date("n")-1]." de ". date("Y"));

    $consulta = "SELECT arr_informacion, usuario, tipo FROM th_licencias_incapacidades WHERE id = '$id'";
    $res = $this->cnn->query('0', $consulta);

    if (count($res) <= 0)
    {
      $this->log->logs("********************Fin1 generar_carta_luto TH***********************");
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No se encontro la informacion de la solicitud";
      return json_encode($respuesta);
    }

    $arr_inf=$res[0]['arr_informacion'];
    $json=json_decode($arr_inf,true);

    foreach($json as $row)
    {
      switch (utf8_decode($row['titulo']))
      {
        case 'Consanguinidad con el fallecido':
          $familiar = utf8_decode($row['informacion']);
          break;
        case 'Fecha de inicio de la licencia';
          $fechaLicencia =  utf8_decode($row['fecha']);
          break;
        case 'Cantidad de dias de permiso';
          $dias = utf8_decode($row['informacion']);
          break;
      }
    }
    foreach($json as $row)
    {
      if(!empty(utf8_decode($row['fecha'])))
      {
        $fechaLicencia = utf8_decode($row['fecha']);
      }
    }

    $cedula = substr($res[0]['usuario'], 2);

    $consulta2 = "SELECT nombres, apellido1 FROM personas
    WHERE documento = '".trim($cedula)."' ";
    $res1=$this->cnn->query('0', $consulta2);
    if (count($res1) <= 0)
    {
      $this->log->logs("********************Fin2 generar_carta_luto TH***********************");
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No se encontro los nombres de la persona";
      return json_encode($respuesta);
    }
    $nombrePersona = $res1[0]['nombres']." ".$res1[0]['apellido1'];

    $tipo = trim($res[0]['tipo']);
    if($tipo == 1)
    {
      $consultaCargo = "SELECT VINCARGO FROM VINCULADO WHERE VINCEDULA = '".$cedula."'";
      $resCargo=$this->cnn->query('5', $consultaCargo);
      if (count($resCargo) <= 0)
      {
        $this->log->logs("********************Fin3 generar_carta_luto TH***********************");
        $respuesta["code"] = 0;
        $respuesta["datos"] = "No se encontro el cargo de la persona";
        return json_encode($respuesta);
      }
      $cargo = $resCargo[0]['VINCARGO'];
    }
    else if($tipo == 2)
    {
      $cargo = "Asesor(a) de Ventas.";
    }

    if (empty($fechaLicencia) || empty($dias) || empty($familiar))
    {
      $this->log->logs("********************Fin4 generar_carta_luto TH***********************");
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No se encontro la informacion de la solicitud";
      return json_encode($respuesta);
    }

    $fechaRegreso = date("d-m-Y", strtotime($fechaLicencia."+ ".trim($dias)." days"));
    $destination_path = $this->ruta . "/public/uploads/talento_humano/soportes/" . $this->env."/";
    $destination_path_firma = $this->ip . "/uploads/talento_humano/firmas/" . $this->env."/".$id."_firma.png";
    $destination_path_salida = $this->ip."/uploads/talento_humano/soportes/" . $this->env."/";
    $name = "th_carta_luto_$id.pdf";

    /* $aumento=0;
    $dias=1;
    while($dias < 6)
    {
      $fechaFini = date("d-m-Y",strtotime($fechaLicencia."+ ".$aumento." days"));
      $day = date("N",strtotime($fechaFini));//Imprime el numero del dia del 1 al 7

      if($day == 7){}
      else {$dias++;}
      $aumento++;
      $fechaFinal = $fechaFini;
    } */

    $fechaFinal = date("d-m-Y",strtotime($fechaRegreso."- 1 days"));
    $festivos = array();
    $trabaja_festivos = true;
    $anio_act = date("Y");
    $anio_siguiente = date("Y", strtotime("+1 year"));
    $fechai_fes="01-01-".$anio_act;
    $fechaf_fes="31-12-".$anio_siguiente;
    $sql_fest = "SELECT date(fecha_festivo) as fest FROM tabla_festivos WHERE date(fecha_festivo) BETWEEN '$fechai_fes' AND '$fechaf_fes'";
    $res_festivos = $this->cnn->query('0', $sql_fest);
    foreach ($res_festivos as $row)
      array_push($festivos, $row['fest']);
    do
    {
      $resultado = $this->validarFecha($fechaRegreso,$festivos);
      $this->log->logs("resultado",array($resultado));
      $fechaRegreso = $resultado[0];
      $encontrado = $resultado[1];
    } while ($encontrado);

    $generate_pdf = $this->generar_pdf_luto($fechaDoc, strtolower($familiar),$fechaLicencia,$fechaRegreso,$nombrePersona,$cargo,$destination_path.$name,$fechaFinal,$destination_path_firma);
    if (empty($generate_pdf))
    {
      $this->log->logs("********************Fin5 generar_carta_luto TH***********************");
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No se pudo generar el PDF";
      return json_encode($respuesta);
    }

    $respuesta["code"] = 1;
    $respuesta["datos"] = "PDF generado";
    $this->log->logs("********************Fin generar_carta_luto TH***********************");
    return json_encode($respuesta);
  }

  private function arr_suspensiones_a_plano($fechai,$fechaf,$tipo_usu)
  {
    $this->log->logs("********************Inicio private arr_suspensiones_a_plano TH***********************");
    $arr_cedulas = array();
    $vec_gamble = array();
    $aux1 = 0;

    $sqlSuspensiones = "SELECT usuario, fechai, dias FROM th_suspensiones ts
      WHERE fechai BETWEEN '$fechai' AND '$fechaf' AND estado = 0 AND tipo_usu='$tipo_usu'";
    $resSuspensiones = $this->cnn->query('0', $sqlSuspensiones);

    if (count($resSuspensiones) > 0)
    {
      foreach ($resSuspensiones as $res)
      {
        $vec_gamble[$aux1][0] = trim($res['usuario']);//usuario
        $vec_gamble[$aux1][1] = trim($res['fechai']);//fechai
        $vec_gamble[$aux1][2] = trim($res['dias']);//dias
        $arr_cedulas[] = "'".trim($res['usuario'])."'";
        $aux1++;
      }
    }
    $r[0] = $vec_gamble;
    $r[1] = $arr_cedulas;
    $this->log->logs("********************Fin private arr_suspensiones_a_plano TH***********************");
    return $r;
  }

  private function calcularFechaFinal($fecha, $dias)
  {
    $fechaObjeto = new DateTime($fecha);
    $fechaObjeto->modify("+ ".($dias-1)." days");
    $fechaFin = $fechaObjeto->format("Y-m-d");
    $fechaObjeto->modify("+ 1 days");
    $fechaIngreso = $fechaObjeto->format("Y-m-d");
    return [$fechaFin,$fechaIngreso];
  }

  private function bloqueoUsuarios($cedulas)
  {
    foreach($cedulas as $cedula)
    {
      $fecha_act = date("Y-m-d H:i:s");
      $sql_up="UPDATE usuarios SET fechafinal=to_date('$fecha_act', 'YYYY-MM-DD HH24:MI:SS'), estado='B', loginregistro = '$cedula[1]'
        WHERE SUBSTR(LOGINUSR,3) = '$cedula[0]'";

      $this->cnn->query('0', $sql_up);
      if($this->env == "dev") $this->cnn->cud_oracle('18', $sql_up);
      else $this->cnn->cud_oracle('2', $sql_up);
      $this->log->logs("###### Se realiza el bloqueo del usuario a las $fecha_act :". json_encode($cedula) ." ########");
    }
  }

  private function desbloqueoUsuarios($cedula)
  {
    $fecha_act = date("Y-m-d H:i:s");
    $sql_up="UPDATE usuarios SET fechafinal=NULL, estado='A' WHERE SUBSTR(LOGINUSR,3) IN ($cedula)";

    $this->cnn->query('0', $sql_up);
    if($this->env == "dev") $this->cnn->cud_oracle('18', $sql_up);
    else $this->cnn->cud_oracle('2', $sql_up);
    $this->log->logs("###### Se realiza el desbloqueo del usuario a las $fecha_act : $cedula ########");
  }

  private function calcularAniosTranscurridos($fecha)
  {
    $fechaDada = new DateTime($fecha);
    $fechaActual = new DateTime();
    $diferencia = $fechaDada->diff($fechaActual);
    $anios = $diferencia->y;
    return ($anios >= 1) ? $anios : 0;
  }

  public function index(Request $request)
  {
  }

  public function solicitudes_reporte(Request $request)
  {
    $this->log->logs("********************Inicia solicitudes_reporte TH***********************");
    $response = new JsonResponse();

    $content = $request->getContent();

    if (empty($content))
    {
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No Llegaron los Datos, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }

    $jsonContent = json_decode($content, true);
    $fechai = (!empty($jsonContent['fechai'])) ? $jsonContent['fechai'] : null;
    $fechaf = (!empty($jsonContent['fechaf'])) ? $jsonContent['fechaf'] : null;

    $fecha_a = date('Y_m_d_H_i_s');
    $dir = $this->ruta . "/public/uploads/talento_humano/archivos/" . $this->env."/";
    $archivo = $this->ruta . "/public/uploads/talento_humano/archivos/". $this->env ."/reporte_th_solicitudes_" . $fecha_a . ".xlsx";

    // $files = glob($dir.'reporte_th_solicitudes_*.xls*'); // Obtiene todos los archivos en el directorio
    // foreach($files as $file)
    // {
    //   if(is_file($file))
    //   {
    //     unlink($file); // Elimina el archivo
    //   }
    // }

    $filesystem = new Filesystem();
    $this->log->logs("archivo ".$archivo);
    if (!is_dir($dir))
    {
      $this->filesystem->mkdir(Path::normalize($dir),0777);
      $filesystem->touch($archivo);
      $filesystem->chmod($archivo, 0777);
    }
    if (!file_exists($archivo))
    {
      $filesystem->touch($archivo);
      $filesystem->chmod($archivo, 0777);
    }

    $vec_gamble_aux = $this->array_solicitudes_excel($fechai,$fechaf);
    $this->log->logs("vec_gamble_aux ",array($vec_gamble_aux));
    $vec_gamble = $vec_gamble_aux[0];

    if (empty($vec_gamble))
    {
      $this->log->logs("********************Fin1 solicitudes_reporte TH***********************");
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No hay datos en ese rango de fechas, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }
    // $this->log->logs("vec_gamble ",array($vec_gamble));

    // $archivo2 = $this->ip."/uploads/talento_humano/archivos/" . $this->env."/th_solicitudes_" . $fecha_a . ".xls";
    // $archivo1 = $this->ruta . "/public/uploads/talento_humano/archivos/". $this->env ."/th_solicitudes_" . $fecha_a . ".xls";
    $archivo2 = $this->ip."/uploads/talento_humano/archivos/" . $this->env."/reporte_th_solicitudes_" . $fecha_a . ".xlsx";
    $archivo1 = $this->ruta . "/public/uploads/talento_humano/archivos/". $this->env ."/reporte_th_solicitudes_" . $fecha_a . ".xlsx";

    //ARMANDO MATRIZ PARA GENERAR EXCEL CON EXPORTCONTROLLER
    $vec_export[0][0] = "ID SOLICITUD";
    $vec_export[0][1] = "TIPO SOLICITUD";
    $vec_export[0][2] = "FECHA REGISTRO SOLICITUD";
    $vec_export[0][3] = "ESTADO";
    $vec_export[0][4] = "CEDULA USUARIO";
    $vec_export[0][5] = "NOMBRE USUARIO";
    $vec_export[0][6] = "AREA";
    $vec_export[0][7] = "TIPO";
    $vec_export[0][8] = "FECHA INICIO LICENCIA";
    $vec_export[0][9] = "DIAS LICENCIA";
    $vec_export[0][10] = "FECHA FIN LICENCIA";
    $vec_export[0][11] = "FECHA INICIO LABORES";

    for ($i = 0; $i < count($vec_gamble); $i++)
    {
      $vec_export[$i+1][0]=trim($vec_gamble[$i][0]);
      $vec_export[$i+1][1]=trim($vec_gamble[$i][1]);
      $vec_export[$i+1][2]=trim($vec_gamble[$i][2]);
      $vec_export[$i+1][3]=trim($vec_gamble[$i][3]);
      $vec_export[$i+1][4]=trim($vec_gamble[$i][4]);
      $vec_export[$i+1][5]=trim($vec_gamble[$i][5]);
      $vec_export[$i+1][6]=trim($vec_gamble[$i][6]);
      $vec_export[$i+1][7]=trim($vec_gamble[$i][7]);

      if(!empty($vec_gamble[$i][9]))
      {
        unset($fecha,$dias);
        $a_json1=json_decode($vec_gamble[$i][9],true);
        foreach($a_json1 as $row)
        {
          $info = "";
          if (array_key_exists('adjunto', $row) && !empty(utf8_decode($row['adjunto'])))
          {
            $info = utf8_decode($row['adjunto']);
          }
          elseif (!empty(utf8_decode($row['descripcion'])))
          {
            $info = utf8_decode($row['descripcion']);
          }
          elseif (!empty(utf8_decode($row['fecha'])))
          {
            $info = utf8_decode($row['fecha']);
          }
          elseif (!empty(utf8_decode($row['informacion'])))
          {
            $info = utf8_decode($row['informacion']);
          }
          $titulo = strtoupper(trim(utf8_decode($row['titulo'])));
          if ($titulo=='FECHA DE NACIMIENTO DEL BEBE' || $titulo=='FECHA DE INICIO DE LA INCAPACIDAD' ||
              $titulo=='FECHA DE INICIO DE LA LICENCIA' || $titulo=='FECHA DE INICIO DEL PERMISO' || $titulo=='DIA DEL PERMISO' || $titulo=='FECHA REMUNERACION')
          {
            if($vec_gamble[$i][11] != 12)
              $vec_export[$i+1][8]=$info;
            $fecha = $info;
          }
          elseif($titulo=='DIAS DE INCAPACIDAD' || $titulo=='CANTIDAD DE DIAS DE PERMISO' || $titulo=='DIAS DE LICENCIA' || $titulo=='CANTIDAD DE DIAS')
          {
            if($vec_gamble[$i][11] != 12)
              $vec_export[$i+1][9]=$info;
            $dias = $info;
            if(trim($vec_gamble[$i][11])==4)
              $vec_export[$i+1][9]=5;
          }
        }
        if($vec_gamble[$i][11] == 12)
        {
          if(empty($fecha))
            $vec_export[$i+1][8]="NO ASIGNADO";
          else
            $vec_export[$i+1][8]=$fecha;
          $vec_export[$i+1][9]=$dias;
        }
        if(($vec_gamble[$i][13]<2 && $vec_gamble[$i][11] != 12) || ($vec_gamble[$i][13]==1 && $vec_gamble[$i][11] == 12))
        {
          $fechaObjeto = new DateTime($fecha);
          $fechaObjeto->modify("+".($dias-1)." days");
          $fecha_final_permiso = $fechaObjeto->format("d-m-Y");
          $fechaObjeto = new DateTime($fecha);
          $fechaObjeto->modify("+".$dias." days");
          $fecha_ingreso = $fechaObjeto->format("d-m-Y");
          $vec_export[$i+1][10]=$fecha_final_permiso;
          $vec_export[$i+1][11]=$fecha_ingreso;
        }
      }
    }

    //INVOCANDO AL CONTROLADOR
    // $request = $this->container->get('request_stack')->getCurrentRequest();
    // $response_excel = $this->forward('App\Controller\ExcelController::generate_excel', [
    //   'info' => json_encode($vec_export),
    //   'name' => json_encode($archivo1),
    // ]);

    $respuesta_excel = $this->excel->excel($vec_export, null, $archivo1);
    if (empty($respuesta_excel))
    {
      $this->log->logs("********************Fin2 solicitudes_reporte TH***********************");
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No se genero el archivo, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }
    $res_excel = json_decode($respuesta_excel, true);
    $this->log->logs("res_excel ",array($res_excel));

    $respuesta["code"] = 1;
    $respuesta["datos"] = $archivo2;
    $respuesta["datos2"] = $archivo1;
    $this->log->logs("********************Fin solicitudes_reporte TH***********************");
    return $response->setContent(json_encode($respuesta));
  }

  public function eliminar_archivo(Request $request)
  {
    $this->log->logs("********************Inicia eliminar_archivo TH***********************");
    $response = new JsonResponse();

    $content = $request->getContent();
    $this->log->logs("content ",array($content));

    if (empty($content))
    {
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No Llegaron los Datos, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }

    $jsonContent = json_decode($content, true);
    $this->log->logs("jsonContent ",($jsonContent));
    $ruta = (!empty($jsonContent['ruta'])) ? $jsonContent['ruta'] : null;
    $this->log->logs("ruta ".($ruta));
    unlink($ruta); // Elimina el archivo

    $respuesta["code"] = 1;
    $respuesta["datos"] = "OK";
    $this->log->logs("********************Fin eliminar_archivo TH***********************");
    return $response->setContent(json_encode($respuesta));
  }

  public function solicitudes_a_plano(Request $request)
  {
    $this->log->logs("********************Inicia solicitudes_a_plano TH***********************");
    $response = new JsonResponse();
    $vec_info_pend = [0,"OK"];
    $content = $request->getContent();

    if (empty($content))
    {
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No Llegaron los Datos, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }

    $this->log->logs("jsonContent ".$content);
    $jsonContent = json_decode($content, true);
    $fechai = (!empty($jsonContent['fechai'])) ? $jsonContent['fechai'] : null;
    $fechaf = (!empty($jsonContent['fechaf'])) ? $jsonContent['fechaf'] : null;
    $tipo_usuario = (!empty($jsonContent['tipo_usuario'])) ? $jsonContent['tipo_usuario'] : null;
    $confirmacion = (!empty($jsonContent['confirmacion'])) ? $jsonContent['confirmacion'] : null;

    if(!$confirmacion) $vec_info_pend = $this->validar_solicitudes_pendientes($fechai,$fechaf,$tipo_usuario);
    if ($vec_info_pend[0] > 0 && !$confirmacion)
    {
      $this->log->logs("********************Fin1 solicitudes_a_plano TH***********************");
      $respuesta["code"] = 2;
      $respuesta["datos"] = $vec_info_pend[1];
      return $response->setContent(json_encode($respuesta));
    }

    $fecha_a = date('Y_m_d_H_i_s');
    $dir = $this->ruta . "/public/uploads/talento_humano/archivos/" . $this->env."/";
    $archivo = $this->ruta . "/public/uploads/talento_humano/archivos/". $this->env ."/reporte_a_plano_solicitudes_" . $fecha_a . ".xlsx";
    $archivo2 = $this->ip."/uploads/talento_humano/archivos/" . $this->env."/reporte_a_plano_solicitudes_" . $fecha_a . ".xlsx";

    $filesystem = new Filesystem();
    $this->log->logs("archivo ".$archivo);
    if (!is_dir($dir))
    {
      $this->filesystem->mkdir(Path::normalize($dir),0777);
      $filesystem->touch($archivo);
      $filesystem->chmod($archivo, 0777);
    }
    if (!file_exists($archivo))
    {
      $filesystem->touch($archivo);
      $filesystem->chmod($archivo, 0777);
    }

    $vec_gamble_aux = $this->array_solicitudes_excel($fechai,$fechaf,2,$tipo_usuario);
    $vec_gamble = $vec_gamble_aux[0];
    $arr_cedulas = $vec_gamble_aux[1];

    $vec_gamble_aux_suspensiones = $this->arr_suspensiones_a_plano($fechai,$fechaf,$tipo_usuario);
    $vec_gamble_suspensiones = $vec_gamble_aux_suspensiones[0];
    $arr_cedulas_suspensiones = $vec_gamble_aux_suspensiones[1];

    if (empty($vec_gamble) && empty($vec_gamble_suspensiones))
    {
      $this->log->logs("********************Fin2 solicitudes_a_plano TH***********************");
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No hay datos en ese rango de fechas, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }

    $vec_manager_suspensiones = array();
    $vec_manager = array();
    $str_cedulas = implode(', ', $arr_cedulas);
    $str_cedulas_suspensiones = implode(', ', $arr_cedulas_suspensiones);
    if(!empty($str_cedulas)) $vec_manager = $this->array_solicitudes_manager_excel_cedula($str_cedulas);
    if(!empty($str_cedulas_suspensiones)) $vec_manager_suspensiones = $this->array_solicitudes_manager_excel_cedula($str_cedulas_suspensiones);

    //ARMANDO MATRIZ PARA GENERAR EXCEL CON EXPORTCONTROLLER
    $vec_export[0][0] = "subirsn.c1";
    $vec_export[0][1] = "tipodoc.c4";
    $vec_export[0][2] = "cedula.c15";
    $vec_export[0][3] = "nombre.c50";
    $vec_export[0][4] = "causa.c5";
    $vec_export[0][5] = "limpio.c50";
    $vec_export[0][6] = "fecini.c10";
    $vec_export[0][7] = "dias.n10";
    $vec_export[0][8] = "canti.n12";
    $vec_export[0][9] = "valor.n12";
    $vec_export[0][10] = "detalle.m4";
    $vec_export[0][11] = "DIAGNOS.C6";
    $vec_export[0][12] = "LIMPIO2.C10";
    $vec_export[0][13] = "LIMPIO3.C250";
    $vec_export[0][14] = "SUCURS.C5";
    $vec_export[0][15] = "CCOSTO.C10";
    $vec_export[0][16] = "DESTINO.C10";
    $vec_export[0][17] = "ZONA.C5";

    $i = 0;
    for ($i = 0; $i < count($vec_gamble); $i++)
    {
      unset($nombre_usu,$fecini,$dias,$diagnosticos,$ccosto,$destino,$fecha_ingreso_lab,$causa,$nueva_fecha);
      $nombre_usu = null;$fecini = null;$dias = null;$diagnosticos = null;$diagnostico = null;$ccosto = null;$destino = null;
      /* $vec_export[$i+1][0]="S";
      $vec_export[$i+1][1]="NV01";
      $vec_export[$i+1][2]=trim($vec_gamble[$i][12]); */

      foreach ($vec_manager as $key)
      {
        if($key[0]==$vec_gamble[$i][12])
        {
          $nombre_usu = $key[1];
          $ccosto = $key[2];
          $destino = $key[3];
          $fecha_ingreso = strval($key[4]);
          break;
        }
      }
      $causa = trim($vec_gamble[$i][10]);

      if($vec_gamble[$i][11]>4 && $vec_gamble[$i][11]<8)
      {
        $fecha_actual = date('Y-m-d');
        $dia = date('d', strtotime($fecha_ingreso));
        if($dia == 1) $nueva_fecha = date('Y-m-d', strtotime('+1 month', strtotime($fecha_ingreso)));
        else $nueva_fecha = date('Y-m-d', strtotime('+2 month', strtotime(date('Y-m-01', strtotime($fecha_ingreso)))));

        $fecha_actual_str = strtotime($fecha_actual);
        $nueva_fecha_str = strtotime($nueva_fecha);
        if($fecha_actual_str<$nueva_fecha_str) $causa = "219";
      }

      /* $vec_export[$i+1][3]=$nombre_usu;
      $vec_export[$i+1][4]=$causa;
      $vec_export[$i+1][5]="."; */

      if(!empty($vec_gamble[$i][9]))
      {
        $a_json1=json_decode($vec_gamble[$i][9],true);
        foreach($a_json1 as $row)
        {
          $info = "";
          if (array_key_exists('adjunto', $row) && !empty(utf8_decode($row['adjunto'])))
          {
            $info = utf8_decode($row['adjunto']);
          }
          elseif (!empty(utf8_decode($row['descripcion'])))
          {
            $info = utf8_decode($row['descripcion']);
          }
          elseif (!empty(utf8_decode($row['fecha'])))
          {
            $info = utf8_decode($row['fecha']);
          }
          elseif (!empty(utf8_decode($row['informacion'])))
          {
            $info = utf8_decode($row['informacion']);
          }
          $titulo = strtoupper(trim(utf8_decode($row['titulo'])));
          if ($titulo=='FECHA DE NACIMIENTO DEL BEBE' || $titulo=='FECHA DE INICIO DE LA INCAPACIDAD' ||
              $titulo=='FECHA DE INICIO DE LA LICENCIA' || $titulo=='FECHA DE INICIO DEL PERMISO' || $titulo=='DIA DEL PERMISO')
          {
            $info = date("Y-m-d", strtotime($info));
            $fecini = $info;
          }
          elseif($titulo=='DIAS DE INCAPACIDAD' || $titulo=='CANTIDAD DE DIAS DE PERMISO' || $titulo=='DIAS DE LICENCIA')
          {
            $dias = $info;
          }
          elseif($titulo=='TRABAJO EL DIA DE INICIO DE LA LICENCIA?' || $titulo=='TRABAJO EL DIA DE INICIO DE LA INCAPACIDAD?')
          {
            if(utf8_decode($row['informacion'])=="Si" && $vec_gamble[$i][11] != 4)
            {
              $dias--;
              $fecini = date('Y-m-d', strtotime(date("Y-m-d",strtotime($fecini."+ 1 days"))));
            }
          }
          if ($vec_gamble[$i][11]<5 || $vec_gamble[$i][11] == 9)
          {
            switch ($vec_gamble[$i][11])
            {
              case '2':
                $diagnostico = "02";
                break;
              case '3':
                $diagnostico = "05";
                break;
              case '4':
                $diagnostico = "01";
                break;
              default:
                $diagnostico = ".";
            }
          }
          else if($titulo=='CODIGO DE ENFERMEDAD')
          {
            if ($info == "AAAA") $info = ".";
            $diagnostico = $info;
          }
        }
      }
      if($dias == 0) continue;

      $vec_export[$i+1][0]="S";
      $vec_export[$i+1][1]="NV01";
      $vec_export[$i+1][2]=trim($vec_gamble[$i][12]);
      $vec_export[$i+1][3]=$nombre_usu;
      $vec_export[$i+1][4]=$causa;
      $vec_export[$i+1][5]=".";

      $vec_export[$i+1][6]=$fecini;
      $vec_export[$i+1][7]=$dias;
      $vec_export[$i+1][8]=$dias;
      $vec_export[$i+1][9]=0;
      $vec_export[$i+1][10]=$vec_gamble[$i][1];
      $vec_export[$i+1][11]=$diagnostico;

      $vec_export[$i+1][12]=".";
      $vec_export[$i+1][13]=".";
      $vec_export[$i+1][14]=".";
      $vec_export[$i+1][15]=$ccosto;
      $vec_export[$i+1][16]=$destino;
      $vec_export[$i+1][17]=".";
    }

    for ($j = 0; $j < count($vec_gamble_suspensiones); $j++)
    {
      unset($nombre_usu,$ccosto,$destino,$causa,$usu_susp,$fechai,$dias);
      $nombre_usu = null;$ccosto = null;$destino = null;
      $usu_susp = $vec_gamble_suspensiones[$j][0];
      $fechai = $vec_gamble_suspensiones[$j][1];
      $dias = $vec_gamble_suspensiones[$j][2];
      foreach ($vec_manager_suspensiones as $key)
      {
        if($key[0]==$usu_susp)
        {
          $nombre_usu = $key[1];
          $ccosto = $key[2];
          $destino = $key[3];
          break;
        }
      }
      $causa = "214";
      $vec_export[$i+1][0]="S";
      $vec_export[$i+1][1]="NV01";
      $vec_export[$i+1][2]=$usu_susp;
      $vec_export[$i+1][3]=$nombre_usu;
      $vec_export[$i+1][4]=$causa;
      $vec_export[$i+1][5]=".";
      $vec_export[$i+1][6]=$fechai;
      $vec_export[$i+1][7]=$dias;
      $vec_export[$i+1][8]=$dias;
      $vec_export[$i+1][9]=0;
      $vec_export[$i+1][10]="Suspension";
      $vec_export[$i+1][11]=".";
      $vec_export[$i+1][12]=".";
      $vec_export[$i+1][13]=".";
      $vec_export[$i+1][14]=".";
      $vec_export[$i+1][15]=$ccosto;
      $vec_export[$i+1][16]=$destino;
      $vec_export[$i+1][17]=".";
      $i++;
    }

    for ($i=0; $i < 18; $i++)
    {
      if($i >= 7 && $i <= 9) $vec_export_tipo[$i] = DataType::TYPE_NUMERIC;
      else $vec_export_tipo[$i] = DataType::TYPE_STRING;
    }

    $respuesta_excel = $this->excel->excel($vec_export, $vec_export_tipo, $archivo);
    if (empty($respuesta_excel))
    {
      $this->log->logs("********************Fin3 solicitudes_a_plano TH***********************");
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No se genero el archivo, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }
    $res_excel = json_decode($respuesta_excel, true);
    $this->log->logs("res_excel ",array($res_excel));

    $respuesta["code"] = 1;
    $respuesta["datos"] = $archivo2;
    $respuesta["datos2"] = $archivo;
    $this->log->logs("********************Fin solicitudes_a_plano TH***********************");
    return $response->setContent(json_encode($respuesta));
  }

  public function metodos(Request $request, ServiceEmail $email)
  {
    $this->log->logs("********************Inicia metodos TH***********************");
    $resp_metodo = null;

    $response = new JsonResponse();
    $content = $request->getContent();
    $this->log->logs("content ",array($content));

    if (empty($content))
    {
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No Llegaron los Datos, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }

    $jsonContent = json_decode($content, true);
    $this->log->logs("jsonContent ",($jsonContent));
    $con = (!empty($jsonContent['con'])) ? $jsonContent['con'] : null;
    $operadorid = (!empty($jsonContent['operadorid'])) ? $jsonContent['operadorid'] : null;
    $fechai = (!empty($jsonContent['fechai'])) ? $jsonContent['fechai'] : null;
    $fechaf = (!empty($jsonContent['fechaf'])) ? $jsonContent['fechaf'] : null;
    $tipo_solicitud = (!empty($jsonContent['tipo_solicitud'])) ? $jsonContent['tipo_solicitud'] : null;
    $estado_solicitud = (!empty($jsonContent['estado_solicitud'])) ? $jsonContent['estado_solicitud'] : null;
    $cc_usuario = (!empty($jsonContent['cc_usuario'])) ? $jsonContent['cc_usuario'] : null;
    $estado_th = (!empty($jsonContent['estado_th'])) ? $jsonContent['estado_th'] : null;
    $comentario_th = (!empty($jsonContent['comentario_th'])) ? $jsonContent['comentario_th'] : null;
    $arr_informacion = (!empty($jsonContent['arr_informacion'])) ? $jsonContent['arr_informacion'] : null;
    $id = (!empty($jsonContent['id'])) ? $jsonContent['id'] : null;
    $fecha = (!empty($jsonContent['fecha'])) ? $jsonContent['fecha'] : null;
    $dias = (!empty($jsonContent['dias'])) ? $jsonContent['dias'] : null;
    $trabajo_el_dia = (!empty($jsonContent['trabajo_el_dia'])) ? $jsonContent['trabajo_el_dia'] : null;
    $labora_festivos = (!empty($jsonContent['labora_festivos'])) ? $jsonContent['labora_festivos'] : null;

    switch ($con)
    {
      case '4'://permite cargar la tabla con las solicitudes del modulo TH
        $resp_metodo = $this->cargar_tabla_ppal_th($operadorid,$fechai,$fechaf,$tipo_solicitud,$estado_solicitud,$cc_usuario);
        break;
      case '5'://permite modificar el estado de una solicitud
        $resp_metodo = $this->actualizar_estado_solicitudes($estado_th,$id,$arr_informacion,$email,$fecha,$dias,$trabajo_el_dia,$comentario_th,$labora_festivos);
        $resp_metodo = json_encode($resp_metodo);
        break;
      default:
        $respuesta["code"] = 0;
        $respuesta["datos"] = "Metodo no definido";
        return $response->setContent(json_encode($respuesta));
    }

    $respuesta["code"] = 1;
    $respuesta["datos"] = $resp_metodo;
    $this->log->logs("********************Fin metodos TH***********************");
    return $response->setContent(json_encode($respuesta));
  }

  public function cargar_solicitud(Request $request)
  {
    $this->log->logs("********************Inicia cargar_solicitud TH***********************");
    $response = new JsonResponse();
    $content = $request->getContent();
    $respuesta["code"] = 0;

    if (empty($content))
    {
      $respuesta["datos"] = "No Llegaron los Datos, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }

    $jsonContent = json_decode($content, true);
    $this->log->logs("jsonContent ",($jsonContent));
    $id_visita = (!empty($jsonContent['id_visita'])) ? $jsonContent['id_visita'] : null;

    $r_metodo = $this->obtener_solicitud_x_id($id_visita);
    if($r_metodo[0]!="1")
    {
      $respuesta["datos"] = $r_metodo[1];
      return $response->setContent(json_encode($respuesta));
    }

    if($r_metodo["solicitud_anulacion"]=="1")
      $r_metodo["r_solicitud_anulacion"] = $this->analisis_solicitud_anulacion(substr($r_metodo["usuario"], 2),$r_metodo["arr_inf"]);
    if($r_metodo["tipo_solicitud"] == "12" && $r_metodo["val_estado"] != "3")
      $r_metodo["analisis_doble_turno"] = $this->analisis_doble_turno($r_metodo["arr_inf"], $r_metodo["fecha_sys"]);

    $festivos = array();
    if($r_metodo["labora_festivos"]=="0" || $r_metodo["tipo_solicitud"] == "3" || $r_metodo["tipo_solicitud"] == "4" || $r_metodo["tipo_solicitud"] == "13")
    {
      $anio_act = date("Y");
      $anio_siguiente = date("Y", strtotime("+1 year"));
      $fechai_fes="01-01-".$anio_act;
      $fechaf_fes="31-12-".$anio_siguiente;
      $sql_fest = "SELECT date(fecha_festivo) as fest FROM tabla_festivos WHERE date(fecha_festivo) BETWEEN '$fechai_fes' AND '$fechaf_fes'";
      $res_festivos = $this->cnn->query('0', $sql_fest);
      foreach ($res_festivos as $row) array_push($festivos, $row['fest']);
    }

    $r_metodo["festivos"] = $festivos;
    $this->log->logs("********************Fin cargar_solicitud TH***********************");
    return $response->setContent(json_encode($r_metodo));
  }

  public function cargar_codigos_enfermedades(Request $request)
  {
    $this->log->logs("********************Inicia cargar_codigos_enfermedades***********************");
    $resp_metodo = null;

    $response = new JsonResponse();
    $content = $request->getContent();
    $this->log->logs("content ",array($content));

    if (empty($content))
    {
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No Llegaron los Datos, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }

    $jsonContent = json_decode($content, true);
    $this->log->logs("jsonContent ",($jsonContent));
    $codigo_enfermedad = (!empty($jsonContent['codigo_enfermedad'])) ? $jsonContent['codigo_enfermedad'] : null;

    $consulta = "SELECT descripcion FROM th_codigos_enfermedades WHERE codigo = '$codigo_enfermedad'";
    $res = $this->cnn->query('0', $consulta);

    if (count($res) <= 0)
    {
      $this->log->logs("********************Fin1 cargar_codigos_enfermedades***********************");
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No se encontro la informacion del codigo de enfermedad";
      return $response->setContent(json_encode($respuesta));
    }

    $descripcion=$res[0]['descripcion'];
    $resp_metodo = $this->array_codigos_enfermedades($codigo_enfermedad);

    $respuesta["code"] = 1;
    $respuesta["descripcion"] = $descripcion;
    $respuesta["datos"] = json_encode($resp_metodo);
    $this->log->logs("********************Fin cargar_codigos_enfermedades***********************");
    return $response->setContent(json_encode($respuesta));
  }

  public function anulaciones_solicitud(Request $request)
  {
    $this->log->logs("********************Inicia anulaciones_solicitud***********************");
    $resp_metodo = null;

    $response = new JsonResponse();
    $content = $request->getContent();
    $this->log->logs("content ",array($content));

    if (empty($content))
    {
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No Llegaron los Datos, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }

    $jsonContent = json_decode($content, true);
    $this->log->logs("jsonContent ",($jsonContent));
    $id = (!empty($jsonContent['id'])) ? $jsonContent['id'] : null;
    $arr_inf = (!empty($jsonContent['arr_inf'])) ? $jsonContent['arr_inf'] : null;
    $parametro = (!empty($jsonContent['parametro'])) ? $jsonContent['parametro'] : null;
    $tipo = (!empty($jsonContent['tipo'])) ? $jsonContent['tipo'] : null;

    switch ($tipo)
    {
      case '1':
        # anular dia especifico
        $resp_metodo = $this->anular_dia_especifico($id,$arr_inf,$parametro);
        if($resp_metodo["code"]==0)
        {
          $this->log->logs("********************Fin1 anulaciones_solicitud***********************");
          $respuesta["code"] = 0;
          $respuesta["datos"] = "No se pudo actualizar, intente nuevamente";
          return $response->setContent(json_encode($respuesta));
        }
        unset($resp_metodo);
        $resp_metodo = "Registro Actualizado";
        break;
      case '2':
        # anular todo
        $sql="UPDATE th_licencias_incapacidades set estado = '3', solicitud_anulacion = '2', fecha_mod=now(),
        usuario_mod='$this->nickname', obs_anula='Anulado por TH por solicitud de usuario',
        obs_rta = 'Anulado por TH por solicitud de usuario' where id = '$id' returning id";
        $res = $this->cnn->query('0', $sql);

        if (count($res) <= 0)
        {
          $this->log->logs("********************Fin2 anulaciones_solicitud***********************");
          $respuesta["code"] = 0;
          $respuesta["datos"] = "No se pudo actualizar, intente nuevamente";
          return $response->setContent(json_encode($respuesta));
        }
        $resp_metodo = "Registro Actualizado";
        break;
      case '3':
        # no anular nada
        $sql="UPDATE th_licencias_incapacidades set solicitud_anulacion = '3',fecha_mod=now(),
        usuario_mod='$this->nickname', obs_rta = 'No se anula por decision de TH' where id = '$id' returning id";
        $res = $this->cnn->query('0', $sql);

        if (count($res) <= 0)
        {
          $this->log->logs("********************Fin3 anulaciones_solicitud***********************");
          $respuesta["code"] = 0;
          $respuesta["datos"] = "No se pudo actualizar, intente nuevamente";
          return $response->setContent(json_encode($respuesta));
        }
        $resp_metodo = "Registro Actualizado";
        break;
    }

    $respuesta["code"] = 1;
    $respuesta["datos"] = json_encode($resp_metodo);
    $this->log->logs("********************Fin anulaciones_solicitud***********************");
    return $response->setContent(json_encode($respuesta));
  }

  public function cargar_usuarios_busqueda()
  {
    $this->log->logs("********************Inicia cargar_usuarios_busqueda***********************");
    $return = array();
    $response = new JsonResponse();

    $sql="SELECT documento, nombres || ' ' || apellido1 as nombre  from personas where documento::text in
    (select substring(usuario,3) from th_licencias_incapacidades group by usuario)";
    $res_sql = $this->cnn->query('0', $sql);

    if (count($res_sql) > 0)
    {
      foreach ($res_sql as $res)
      {
        $return[] = array(
          "cedula" => trim($res['documento']),
          "nombre" => $res['nombre']
        );
      }
    }

    $respuesta["code"] = 1;
    $respuesta["datos"] = json_encode($return);
    $this->log->logs("********************Fin cargar_usuarios_busqueda***********************");
    return $response->setContent(json_encode($respuesta));
  }

  //metodo por modificar porque el modulo se va a dejar pendiente
  public function agregar_areas_cargos(Request $request)//metodo que pemite aniadir nuevas areas con sus respectivos cargos
  {
    $this->log->logs("********************Inicia agregar_areas_cargos TH***********************");
    $response = new JsonResponse();
    $content = $request->getContent();
    $this->log->logs("content ",array($content));
    $respuesta = array();

    if (empty($content))
    {
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No Llegaron los Datos, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }

    $jsonContent = json_decode($content, true);
    $this->log->logs("jsonContent ",($jsonContent));
    $nombreArea = (!empty($jsonContent['nombreArea'])) ? $jsonContent['nombreArea'] : null;
    $isSubArea = (!empty($jsonContent['isSubArea'])) ? $jsonContent['isSubArea'] : null;
    $areaRel = (!empty($jsonContent['areaRel'])) ? $jsonContent['areaRel'] : null;
    $arrDatos = (!empty($jsonContent['arrDatos'])) ? $jsonContent['arrDatos'] : null;

    $sql="SELECT id FROM th_personal_administrativo_areas WHERE nombre = '$nombreArea'";
    $res_sql = $this->cnn->query('0', $sql);

    if (count($res_sql) > 0)
    {
      $this->log->logs("********************Fin1 agregar_areas_cargos TH***********************");
      $respuesta["code"] = 0;
      $respuesta["datos"] = "Ya existe un Area con ese nombre, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }

    $values = "(nombre) VALUES ('$nombreArea')";

    if ($isSubArea)
      $values = "(nombre, tipo, id_area_encargada) VALUES ('$nombreArea', 1, $areaRel)";

    $sql_insert = "INSERT INTO th_personal_administrativo_areas $values RETURNING id";
    $res_sql_insert = $this->cnn->query('0', $sql_insert);

    if (count($res_sql_insert) <= 0)
    {
      $this->log->logs("********************Fin2 agregar_areas_cargos TH***********************");
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No se pudo insertar el area, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }
    $id_area=$res_sql_insert[0]['id'];
    $arrFallas = $this->registrar_cargos_asignaciones($arrDatos,$id_area);

    if (empty($arrFallas))
    {
      $respuesta["code"] = 1;
      $respuesta["datos"] = "OK";
    }
    else
    {
      $respuesta["code"] = 2;
      $respuesta["datos"] = json_encode($arrFallas);
    }

    $this->log->logs("********************Fin agregar_areas_cargos TH***********************");
    return $response->setContent(json_encode($respuesta));
  }

  public function agregar_cargo_temporal(Request $request)//metodo que pemite aniadir cargos temporales
  {
    $this->log->logs("********************Inicia agregar_cargo_temporal TH***********************");
    $response = new JsonResponse();
    $content = $request->getContent();
    $this->log->logs("content ",array($content));
    $respuesta = array();

    if (empty($content))
    {
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No Llegaron los Datos, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }

    $jsonContent = json_decode($content, true);
    $this->log->logs("jsonContent ",($jsonContent));
    $info_cargo = (!empty($jsonContent['id_cargo'])) ? $jsonContent['id_cargo'] : null;
    $cedula = (!empty($jsonContent['cedula'])) ? $jsonContent['cedula'] : null;
    $fechai = (!empty($jsonContent['fechai'])) ? $jsonContent['fechai'] : null;
    $fechaf = (!empty($jsonContent['fechaf'])) ? $jsonContent['fechaf'] : null;
    $arr_info_cargo = explode(",", $info_cargo);
    $id_cargo = $arr_info_cargo[0];

    $sql_updt = "UPDATE th_cargos_temporales SET estado=1,fecha_mod=now(),
    usuario_mod='$this->nickname' WHERE id_cargo = '$id_cargo'";
    $this->cnn->query('0', $sql_updt);

    /* if (count($res_sql_updt) <= 0)
    {
      $this->log->logs("********************Fin3 agregar_cargo_temporal TH***********************");
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No se pudo actualizar los cargos, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    } */

    $sql_personal="SELECT id FROM th_personal_administrativo WHERE cedula = '$cedula'";
    $res_sql_personal = $this->cnn->query('0', $sql_personal);
    if (count($res_sql_personal) <= 0)
    {
      $this->log->logs("********************Fin1 agregar_cargo_temporal TH***********************");
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No existe el usuario, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }
    $id_usu_enc = $res_sql_personal[0]["id"];

    $sql_insert_cargos = "INSERT INTO th_cargos_temporales (id_cargo, id_per_adm_usuario, fechai, fechaf) VALUES ('$id_cargo', '$id_usu_enc', '$fechai', '$fechaf') RETURNING id";
    $res_sql_insert_cargos = $this->cnn->query('0', $sql_insert_cargos);
    if (count($res_sql_insert_cargos) <= 0)
    {
      $this->log->logs("********************Fin2 agregar_cargo_temporal TH***********************");
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No se pudo registrar el cargo temporal, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }

    $respuesta["code"] = 1;
    $respuesta["datos"] = "OK";
    $this->log->logs("********************Fin agregar_cargo_temporal TH***********************");
    return $response->setContent(json_encode($respuesta));
  }

  public function eliminar_cargo_temporal(Request $request)//metodo que pemite inactivar cargos temporales
  {
    $this->log->logs("********************Inicia eliminar_cargo_temporal TH***********************");
    $response = new JsonResponse();
    $content = $request->getContent();
    $this->log->logs("content ",array($content));
    $respuesta = array();

    if (empty($content))
    {
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No Llegaron los Datos, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }

    $jsonContent = json_decode($content, true);
    $this->log->logs("jsonContent ",($jsonContent));
    $info_cargo = (!empty($jsonContent['id_cargo'])) ? $jsonContent['id_cargo'] : null;
    $arr_info_cargo = explode(",", $info_cargo);
    $id_cargo = $arr_info_cargo[0];

    $sql_updt = "UPDATE th_cargos_temporales SET estado=1,fecha_mod=now(),
    usuario_mod='$this->nickname' WHERE id_cargo = '$id_cargo' returning id";
    $res_sql_updt = $this->cnn->query('0', $sql_updt);

    if (count($res_sql_updt) <= 0)
    {
      $this->log->logs("********************Fin1 eliminar_cargo_temporal TH***********************");
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No se pudo eliminar el cargo, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }

    $respuesta["code"] = 1;
    $respuesta["datos"] = "OK";
    $this->log->logs("********************Fin eliminar_cargo_temporal TH***********************");
    return $response->setContent(json_encode($respuesta));
  }

  public function areas()//consulta las areas guardadas en SGC
  {
    $this->log->logs("********************Inicia areas TH***********************");
    $response = new JsonResponse();
    $sql = "SELECT id, nombre FROM th_personal_administrativo_areas WHERE estado = '0' ORDER BY id ASC";
    $res_sql=$this->cnn->query('0', $sql);

    $vec=array();
    $vec[0]['respuesta2'] = "";
    $vec[0]['respuesta3'] = "";
    $vec[0]['respuesta4'] = "";
    $vec_cargos=array();
    $vec_cargos_temporales=array();
    $vec_cargos_disponibles=array();
    $vec_ccostos=array();

    $cont=1;
    if(count($res_sql) <= 0)
    {
      $vec[0]['tipo'] = 2;
      $vec[0]['respuesta'] = "ERROR EN BUSQUEDA";
      return $response->setContent(json_encode($vec));
    }

    $contenido = "<option value=0>SELECCIONE EL AREA</option>";
    $vec[0]['tipo'] = 1;
    $vec[0]['respuesta'] = "<option value=0>SELECCIONE EL AREA</option>";

    foreach($res_sql as $res)
    {
      $id = $res['id'];
      $area = $res['nombre'];
      $contenido.= '<option value="'.$id.'">'.$area.'</option>';

      unset($area,$id);
      $cont++;
    }
    $vec[0]['tipo'] = 1;
    $vec[0]['respuesta'] = $contenido;

    $sql_cargos = "SELECT id, nombre, id_per_adm_area FROM th_cargos WHERE estado=0 and tipo=1";
    $res_sql_cargos=$this->cnn->query('0', $sql_cargos);

    if(count($res_sql_cargos) <= 0)
    {
      $this->log->logs("********************Fin1 areas TH***********************");
      return $response->setContent(json_encode($vec));
    }

    foreach($res_sql_cargos as $res)
      array_push($vec_cargos, array("id" => trim($res['id']), "cargo" => trim($res['nombre']), "id_per_adm_area" => trim($res['id_per_adm_area'])));
    $vec[0]['respuesta2'] = json_encode($vec_cargos);

    $sql_cargos_temporales = "SELECT c.id_cargo, p.cedula, c.fechai, c.fechaf FROM th_cargos_temporales c JOIN th_personal_administrativo p ON c.id_per_adm_usuario = p.id
    WHERE c.estado = 0 AND c.fechaf >= DATE(NOW()) and p.estado=0";
    $res_sql_cargos_temporales=$this->cnn->query('0', $sql_cargos_temporales);

    /* if(count($res_sql_cargos_temporales) <= 0)
    {
      $this->log->logs("********************Fin2 areas TH***********************");
      return $response->setContent(json_encode($vec));
    } */

    $cedulas = array();
    foreach($res_sql_cargos_temporales as $res)
    {
      $cedulas[] = trim($res['cedula']);
      array_push($vec_cargos_temporales, array("id_cargo" => trim($res['id_cargo']), "cedula" => trim($res['cedula']),
      "fechai" => trim($res['fechai']), "fechaf" => trim($res['fechaf']), "nombre" => ""));
    }

    $cedulas_separadas_por_comas = "'" . implode("','", $cedulas) . "'";
    $sql2 = "SELECT VINNOMBRE, VINCEDULA FROM VINCULADO WHERE VINCEDULA in ($cedulas_separadas_por_comas)";
    $res_sql2=$this->cnn->query('5', $sql2);

    /* if(count($res_sql2)<=0)
    {
      $this->log->logs("********************Fin3 areas TH***********************");
      $vec[0]['respuesta3'] = json_encode($vec_cargos_temporales);
      return $response->setContent(json_encode($vec));
    } */

    foreach ($vec_cargos_temporales as &$registro)
    {
      $cedula = $registro['cedula'];
      foreach($res_sql2 as $res2)
      {
        if ($cedula == trim($res2['VINCEDULA']))
        {
          $registro['nombre'] = trim($res2['VINNOMBRE']);
          break;
        }
      }
    }
    unset($registro);
    $vec[0]['respuesta3'] = json_encode($vec_cargos_temporales);

    $sql_cargos_disp = "SELECT c.id, c.nombre, c.id_per_adm_area, c.tipo, COALESCE(tc.cantidad,0) as cantidad FROM th_cargos c
    LEFT JOIN th_personal_administrativo_asignaciones a ON c.id = a.id_per_adm_cargo AND a.estado = 0
    LEFT JOIN (SELECT tc.id_cargo_permiso, count(*) AS cantidad FROM th_cargos tc WHERE tc.estado='0' GROUP BY tc.id_cargo_permiso) tc
    ON tc.id_cargo_permiso = c.id
    WHERE c.estado = 0 AND (c.tipo = 0 OR (c.tipo = 1 AND a.id_per_adm_cargo IS NULL)) group by c.id, tc.cantidad ";
    $res_sql_cargos_disp=$this->cnn->query('0', $sql_cargos_disp);

    /* if(!(count($res_sql_cargos_disp) > 0))
    {
      $this->log->logs("********************Fin4 areas TH***********************");
      $response->setContent(json_encode($vec));
      return $response;
      exit(0);
    } */

    foreach($res_sql_cargos_disp as $res)
      array_push($vec_cargos_disponibles, array("id" => trim($res['id']), "cargo" => trim($res['nombre']),
      "tipo" => trim($res['tipo']), "id_per_adm_area" => trim($res['id_per_adm_area']), "jefe" => trim($res['cantidad']) > 0 ? 1 : 0));
    $vec[0]['respuesta4'] = json_encode($vec_cargos_disponibles);

    $sql_ccostos = "SELECT t2.codigo,t2.nombre from ubicacionnegocios u ,territorios t1, territorios t2 where TRTRIO_TPOTRT_CODIGO='14' and t1.codigo=u.trtrio_codigo_compuesto_de and t2.codigo=u.trtrio_codigo and u.fechafinal is null
    and t1.codigo not in ('1077','3034','3482','3330','2947','3813','3220','4200','4194','4198','4204','4139','4407','4206','4196','4202','4141')";
    $res_sql_ccostos=$this->cnn->query('2', $sql_ccostos);

    array_push($vec_ccostos, array("codigo" => 0, "nombre" => "SELECCIONE", "estado" => "0"));
    foreach($res_sql_ccostos as $res)
      array_push($vec_ccostos, array("codigo" => trim($res['CODIGO']), "nombre" => trim($res['NOMBRE']), "estado" => "2"));

    $sql_ccostos_adm = "SELECT ccosto FROM th_ccostos_administrativo";
    $res_ccostos_adm=$this->cnn->query('0', $sql_ccostos_adm);
    foreach($res_ccostos_adm as $res)
    {
      foreach($vec_ccostos as &$ccosto)
      {
        if ($ccosto['codigo'] == $res["ccosto"])
        {
          $ccosto['estado'] = "1";
          break;
        }
      }
      unset($ccosto);
    }
    $vec[0]['respuesta5'] = json_encode($vec_ccostos);

    $this->log->logs("********************Fin areas TH***********************");
    $response->setContent(json_encode($vec));
    return $response;
  }

  public function consultaNomina()//consultar los usuarios registrados en la tabla de th_personal_administrativo
  {
    $this->log->logs("********************Inicia consultaNomina TH***********************");
    /* $sql = "SELECT cedula FROM th_personal_administrativo"; */

    $sql = "SELECT cedula FROM th_personal_administrativo
    WHERE estado = '0' AND (tipo = '0' OR (tipo = '1' AND CURRENT_DATE BETWEEN fechai AND fechaf))";
    $res_sql=$this->cnn->query('0', $sql);

    $arr_cedulas=[];
    $vec=array();
    foreach($res_sql as $res)
    {
      $cedula = "'".$res['cedula']."'";
      array_push($arr_cedulas, $cedula);
    }
    $cedulas = implode(', ', $arr_cedulas);

    if(count($res_sql)>0)
    {
      $sql2 = "SELECT VINCEDULA AS CEDULA, VINNOMBRE AS NOMBRE FROM VINCULADO WHERE VINCEDULA in ($cedulas)";
      $res_sql2=$this->cnn->query('5', $sql2);

      $cont=0;
      foreach($res_sql2 as $res2)
      {
        $cedula = trim($res2['CEDULA']);
        $nombre = trim($res2['NOMBRE']);

        $vec[$cont]['tipo'] = 1;
        $vec[$cont]['cedula'] = $cedula;
        $vec[$cont]['nombre'] = $nombre;

        unset($cedula);
        unset($nombre);
        $cont++;
      }
    }

    $consulta = "SELECT PRS_DOCUMENTO as CEDULA FROM CONTRATOPERSONAS WHERE UBCNEG_TRTRIO_CODIGO = '4140'";
    $result1=$this->cnn->query('2', $consulta);

    $arrayCedulas = [];
    $arrayCedulas2 = [];
    foreach($result1 as $res)
    {
      $cedula="'".$res['CEDULA']."'";
      array_push($arrayCedulas, $cedula);
      array_push($arrayCedulas2, trim($res['CEDULA']));
    }
    $cedulas_2 = implode(', ', $arrayCedulas);

    $manager = "SELECT VINCEDULA as CEDULA, VINNOMBRE as NOMBRE FROM VINCULADO WHERE
    VINCEDULA in ($cedulas_2) and VINCEDULA not in ($cedulas) AND VINFECRET > TO_DATE('".date("d/m/Y", strtotime("-1 day"))."', 'DD/MM/YYYY')";
    $resNombres = $this->cnn->query('5', $manager);

    foreach($resNombres as $nom)
    {
      $vec[$cont]['cedula'] = trim($nom['CEDULA']);
      $vec[$cont]['nombre'] = trim($nom['NOMBRE']);
      $vec[$cont]['tipo'] = 2;
      $cont++;
    }

    $fechaHoy = date("d/m/Y");
    $sql3 = "SELECT v.VINCEDULA AS CEDULA, v.VINNOMBRE AS NOMBRE FROM VINCULADO v, NMCARGO m, NMEMPLEADO n, PARAMETRO p, MNGDNO d
    WHERE n.NEMDESTINO=d.DNOCODIGO AND p.PARCODIGO=n.NEMTPCONTR AND v.VINCEDULA=n.NEMCEDULA AND m.NCRCODIGO=n.NEMCARGO
    AND v.VINFECRET > TO_DATE('$fechaHoy', 'DD/MM/YYYY') AND p.PARCODIGO in('NM0901', 'NM0902', 'NM0905') AND n.NEMCARGO not in('3502', '3503')
    AND v.VINACTIVO = '1' AND v.VINEMPLEA = '1' AND v.VINCEDULA NOT IN ($cedulas) ORDER BY v.VINCEDULA ASC";
    $res_sql3=$this->cnn->query('5', $sql3);

    foreach($res_sql3 as $res2)
    {
      $cedula = trim($res2['CEDULA']);
      $nombre = trim($res2['NOMBRE']);

      $vec[$cont]['tipo'] = 2;
      $vec[$cont]['cedula'] = $cedula;
      $vec[$cont]['nombre'] = $nombre;

      unset($cedula);
      unset($nombre);
      $cont++;
    }

    /* $sql_asesoras_betplay = "SELECT substr(u.loginusr,3) AS cedula FROM usuarios u
    JOIN gamble_70.contratopersonas c ON c.login = u.loginusr AND c.fechafinal IS null
    WHERE u.nivel IN ('67') AND u.estado = 'A' AND substr(u.loginusr,3) not in ($cedulas)";
    $res_sql_asesoras_betplay=$this->cnn->query('0', $sql_asesoras_betplay);
    $this->log->logs("sql_asesoras_betplay ".$sql_asesoras_betplay);

    $arr_cedulas = [];
    foreach($res_sql_asesoras_betplay as $res)
    {
      array_push($arr_cedulas, $res['cedula']);
    } */

    $sql4 = "SELECT v.VINCEDULA AS CEDULA, v.VINNOMBRE AS NOMBRE
    FROM VINCULADO v, NMCARGO m, NMEMPLEADO n, PARAMETRO p
    WHERE p.PARCODIGO=n.NEMTPCONTR AND v.VINCEDULA=n.NEMCEDULA AND m.NCRCODIGO=n.NEMCARGO AND v.VINFECRET > TO_DATE('$fechaHoy', 'DD/MM/YYYY')
    AND p.PARCODIGO in('NM0901', 'NM0902') AND n.NEMCARGO in('3502', '3503') AND v.VINACTIVO = '1' AND v.VINEMPLEA = '1' AND v.VINCEDULA NOT IN ($cedulas)";
    $res_sql4=$this->cnn->query('5', $sql4);

    foreach($res_sql4 as $res2)
    {
      if (in_array(trim($res2['CEDULA']), $arrayCedulas2)) continue;
      $cedula = trim($res2['CEDULA']);
      $nombre = trim($res2['NOMBRE']);

      $vec[$cont]['tipo'] = 3;
      $vec[$cont]['cedula'] = $cedula;
      $vec[$cont]['nombre'] = $nombre;

      unset($cedula);
      unset($nombre);
      $cont++;
    }

    // $this->log->logs("vec ",array($vec));
    $response = new JsonResponse();
    $this->log->logs("********************Fin consultaNomina TH***********************");
    return $response->setContent(json_encode($vec));
  }

  public function buscarEncargado(Request $request)//busca la informacion de los encargados de cada area
  {
    $this->log->logs("********************Inicia buscarEncargado TH***********************");
    $response = new JsonResponse();
    $content = $request->getContent();
    $respuesta = array();
    $arrDatos = array();

    if (empty($content))
    {
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No Llegaron los Datos, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }

    $jsonContent = json_decode($content, true);
    $area=(!empty($jsonContent['area'])) ? $jsonContent['area'] : null;

    $sql = "SELECT t1.cedula, t3.nombre as cargo, t3.id as id_cargo, t4.nombre, t4.tipo as area, t4.tipo, t4.id_area_encargada
    FROM th_cargos t3
    LEFT JOIN th_personal_administrativo_asignaciones t2 ON t3.id = t2.id_per_adm_cargo AND t2.estado = 0
    LEFT JOIN th_personal_administrativo t1 ON t2.id_per_adm_usuario_encargado = t1.id AND t1.estado = 0
    INNER JOIN th_personal_administrativo_areas t4 ON t4.id = t3.id_per_adm_area AND t4.estado = 0
    WHERE t4.id = '$area' AND t3.estado = 0 AND t3.tipo=1
    ORDER BY t3.id ASC";
    $res_sql=$this->cnn->query('0', $sql);

    $cedulas = array();
    foreach($res_sql as $res)
    {
      if(!empty(trim($res['cedula'])))
        $cedulas[] = trim($res['cedula']);
      array_push($arrDatos, array("cedula" => trim($res['cedula']), "nombre" => "", "area" => trim($res['area']),
      "cargo" => trim($res['cargo']), "id_cargo" => trim($res['id_cargo']), "tipo" => trim($res['tipo']), "id_area_encargada" => trim($res['id_area_encargada'])));
    }

    if (count($arrDatos)==0)
    {
      $this->log->logs("********************Fin1 buscarEncargado TH***********************");
      $respuesta["code"] = 2;
      $respuesta["datos"] = "No se Encontraron cargos asignados al area";
      return $response->setContent(json_encode($respuesta));
    }

    $cedulas_separadas_por_comas = "'" . implode("','", $cedulas) . "'";
    if($cedulas_separadas_por_comas=="''")
    {
      $this->log->logs("********************Fin2 buscarEncargado TH***********************");
      $respuesta["code"] = 1;
      $respuesta["datos"] = json_encode($arrDatos);
      return $response->setContent(json_encode($respuesta));
    }
    $sql2 = "SELECT VINNOMBRE, VINCEDULA FROM VINCULADO WHERE VINCEDULA in ($cedulas_separadas_por_comas)";
    $res_sql2=$this->cnn->query('5', $sql2);

    if(count($res_sql2)<=0)
    {
      $this->log->logs("********************Fin2 buscarEncargado TH***********************");
      $respuesta["code"] = 0;
      $respuesta["datos"] = "Hubo un Error en Consulta a Manager Intente Nuevamente";
      return $response->setContent(json_encode($respuesta));
    }

    foreach ($arrDatos as &$registro)
    {
      $cedula = $registro['cedula'];
      foreach($res_sql2 as $res2)
      {
        if ($cedula == trim($res2['VINCEDULA']))
        {
          $registro['nombre'] = trim($res2['VINNOMBRE']);
          break;
        }
      }
    }
    unset($registro);

    $respuesta["code"] = 1;
    $respuesta["datos"] = json_encode($arrDatos);
    $this->log->logs("********************Fin buscarEncargado TH***********************");
    return $response->setContent(json_encode($respuesta));
  }

  public function eliminar_area(Request $request)//Inactiva el area
  {
    $this->log->logs("********************Inicia eliminar_area TH***********************");
    $response = new JsonResponse();
    $content = $request->getContent();
    $respuesta = array();

    if (empty($content))
    {
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No Llegaron los Datos, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }
    $jsonContent = json_decode($content, true);
    $area=(!empty($jsonContent['area'])) ? $jsonContent['area'] : null;

    $sql = "UPDATE th_personal_administrativo_areas set estado = '1',fecha_mod=now(),
    usuario_mod='$this->nickname' where id = '$area' returning id";
    $res_sql=$this->cnn->query('0', $sql);

    if(count($res_sql)<=0)
    {
      $this->log->logs("********************Fin1 eliminar_area TH***********************");
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No se pudo eliminar el Area, intente nuevamente.";
      return $response->setContent(json_encode($respuesta));
    }

    $respuesta["code"] = 1;
    $respuesta["datos"] = "OK";
    $this->log->logs("********************Fin eliminar_area TH***********************");
    return $response->setContent(json_encode($respuesta));
  }

  public function cambiarEncargado(Request $request)
  {
    $this->log->logs("********************Inicia cambiarEncargado TH***********************");
    $response = new JsonResponse();
    $content = $request->getContent();
    $this->log->logs("content ",array($content));
    $respuesta = array();

    if (empty($content))
    {
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No Llegaron los Datos, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }

    $jsonContent = json_decode($content, true);
    $this->log->logs("jsonContent ",($jsonContent));
    $id_area = (!empty($jsonContent['area'])) ? $jsonContent['area'] : null;
    /* $isSubArea = (!empty($jsonContent['isSubArea'])) ? $jsonContent['isSubArea'] : null;
    $areaRel = (!empty($jsonContent['areaRel'])) ? $jsonContent['areaRel'] : null; */
    $arrDatos = (!empty($jsonContent['arrDatos'])) ? $jsonContent['arrDatos'] : null;

    /* $values = "tipo = 0, id_area_encargada = NULL";
    if ($isSubArea)
      $values = "tipo = 1, id_area_encargada = $areaRel";

    $sql_updt = "UPDATE th_personal_administrativo_areas SET $values WHERE id = '$id_area'";
    $res_sql_updt = $this->cnn->query('0', $sql_updt);

    if (count($res_sql_updt) <= 0)
    {
      $this->log->logs("********************Fin1 cambiarEncargado TH***********************");
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No se pudo actualizar el area, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    } */

    $sql_inactivar = "UPDATE TH_PERSONAL_ADMINISTRATIVO_ASIGNACIONES set estado = '1',fecha_mod=now(),
    usuario_mod='$this->nickname'
    where id_per_adm_cargo in (SELECT id FROM th_cargos where id_per_adm_area = '$id_area' and estado=0 and tipo=1)";
    $res_sql_inactivar = $this->cnn->query('0', $sql_inactivar);

    /* $sql_inactivar2 = "UPDATE th_cargos set estado = '1' where id_per_adm_area = '$id_area'";
    $res_sql_inactivar2 = $this->cnn->query('0', $sql_inactivar2);

    if (!($res_sql_inactivar && $res_sql_inactivar2)) */
    /* if (!$res_sql_inactivar)
    {
      $this->log->logs("********************Fin2 cambiarEncargado TH***********************");
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No se pudo inactivar los registros anteriores, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    } */

    $arrFallas = $this->registrar_cargos_asignaciones($arrDatos,$id_area);
    if (empty($arrFallas))
    {
      $respuesta["code"] = 1;
      $respuesta["datos"] = "OK";
    }
    else
    {
      $respuesta["code"] = 2;
      $respuesta["datos"] = json_encode($arrFallas);
    }

    $this->log->logs("********************Fin cambiarEncargado TH***********************");
    return $response->setContent(json_encode($respuesta));
  }

  public function agregarEnfermedad(Request $request)
  {
    $this->log->logs("********************Inicia agregarEnfermedad TH***********************");
    $response = new JsonResponse();
    $content = $request->getContent();
    $this->log->logs("content ",array($content));
    $jsonContent = json_decode($content, true);

    $codigo=(!empty($jsonContent['codigo'])) ? $jsonContent['codigo'] : null;
    $nombre=(!empty($jsonContent['nombre'])) ? $jsonContent['nombre'] : null;

    $validador = "SELECT codigo FROM th_codigos_enfermedades
    WHERE codigo = '".$codigo."'";
    $res_valid = $this->cnn->query('0', $validador);

    $vec=array();
    if(count($res_valid) > 0)
    {
      $vec[0]['tipo'] = 2;
      $vec[0]['respuesta'] = "Este Codigo Ya existe, valide";
      return $response->setContent(json_encode($vec));
    }

    $sql = "INSERT INTO th_codigos_enfermedades (codigo, descripcion) VALUES ('".$codigo."', '".$nombre."') returning id";
      $res_sql=$this->cnn->query('0', $sql);

      if(count($res_sql)>0)
      {
        $vec[0]['tipo'] = 1;
        $vec[0]['respuesta'] = "Se Inserto Correctamente";
      }
    else
    {
        $vec[0]['tipo'] = 2;
        $vec[0]['respuesta'] = "Hubo un Error en el servicio SGC";
    }

    $this->log->logs("********************Fin agregarEnfermedad TH***********************");
    return $response->setContent(json_encode($vec));
  }

  public function consultaEnfermedades()
  {
    $this->log->logs("********************Inicia consultaEnfermedades TH***********************");
    $sql = "SELECT codigo, descripcion FROM th_codigos_enfermedades";
    $res_sql=$this->cnn->query('0', $sql);

    $vec=array();
    $cont=0;
    foreach($res_sql as $res)
    {
      $codigo = trim($res['codigo']);
      $nombre = trim($res['descripcion']);

      $vec[$cont]['codigo']=$codigo;
      $vec[$cont]['nombre']=$nombre;

      unset($codigo);
      unset($nombre);
      $cont++;
    }

    $response = new JsonResponse();
    $this->log->logs("********************Fin consultaEnfermedades TH***********************");
    return $response->setContent(json_encode($vec));
  }

  public function mostrarEnfermedad(Request $request)
  {
    $this->log->logs("********************Inicia mostrarEnfermedad TH***********************");
    $response = new JsonResponse();
    $content = $request->getContent();
    $jsonContent = json_decode($content, true);
    $code=(!empty($jsonContent['code'])) ? $jsonContent['code'] : null;

    $sql = "SELECT codigo, descripcion, estado FROM th_codigos_enfermedades
    WHERE codigo = '".$code."'";
    $res_sql=$this->cnn->query('0', $sql);

    $vec=array();

    if(count($res_sql)<=0)
    {
      $vec[0]['tipo']=2;
      $vec[0]['contenido']= "No se Logro Encontrar el Codigo.";
      return $response->setContent(json_encode($vec));
    }

      foreach($res_sql as $res)
      {
        $codigo = $res['codigo'];
        $nombre = $res['descripcion'];

        if(trim($res['estado']) == 0)
        {
          $estado = "<input type=checkbox value=0 id=campoEstado checked=checked style='width:25px;height:50px;' onclick=checar()>";
        }
        else {
          $estado = "<input type=checkbox value=1 id=campoEstado style='width:25px;height:50px;' onclick=checar()>";
        }
      }
      $contenido = "<tr>";
      $contenido.= "<td align=center style='background-color:#ffffff;border:solid black 1px;' height=50px>".$codigo."<input type=hidden value='".$codigo."' id=campoCodigo></td>";
      $contenido.= "<td align=center style='background-color:#ffffff;border:solid black 1px;' height=50px>
      <div class='input-group mb-3'>
        <div class='input-group-prepend'>
          <div class='input-group-text'>
            <input type='checkbox' aria-label='Checkbox for following text input' id='checkNombre' onclick='cambiarNombre()'>
          </div>
        </div>
        <input type='text' class='form-control' value='".$nombre."' id='campoNombre' disabled  onkeyup=javascript:this.value=this.value.toUpperCase();>
      </div>
      </td>";
      $contenido.= "<td align=center style='background-color:#ffffff;border:solid black 1px;' height=50px>".$estado."</td></tr>";

      $vec[0]['tipo']=1;
      $vec[0]['contenido']=$contenido;

    $this->log->logs("********************Fin mostrarEnfermedad TH***********************");
    return $response->setContent(json_encode($vec));
  }

  public function checarEstado(Request $request)
  {
    $this->log->logs("********************Inicia checarEstado TH***********************");
    $content = $request->getContent();
    $jsonContent = json_decode($content, true);

    $codigo=(!empty($jsonContent['codigo'])) ? $jsonContent['codigo'] : null;
    $estado=(!empty($jsonContent['estado'])) ? $jsonContent['estado'] : null;
    $numeroEstado = explode("-", $estado);

    $sql = "UPDATE th_codigos_enfermedades SET estado = '".$numeroEstado[1]."',fecha_mod=now(),
    usuario_mod='$this->nickname'
    WHERE codigo = '$codigo' returning id";
    $res_sql=$this->cnn->query('0', $sql);

    $vec=array();
    if(count($res_sql)>0)
    {
      $vec[0]['tipo'] = 1;
      $vec[0]['respuesta'] = "Se Cambio el Estado.";
    }
    else
    {
      $vec[0]['tipo'] = 2;
      $vec[0]['respuesta'] = "No se Cambio.";
    }

    $response = new JsonResponse();
    $this->log->logs("********************Fin checarEstado TH***********************");
    return $response->setContent(json_encode($vec));
  }

  public function cambiarNombre(Request $request)//cambia descripcion de una enfermedad por el codigo
  {
    $this->log->logs("********************Inicia cambiarNombre TH***********************");
    $content = $request->getContent();
    $jsonContent = json_decode($content, true);

    $codigo=(!empty($jsonContent['codigo'])) ? $jsonContent['codigo'] : null;
    $nombre=(!empty($jsonContent['nombre'])) ? $jsonContent['nombre'] : null;

    $sql = "UPDATE th_codigos_enfermedades SET descripcion = '$nombre',fecha_mod=now(),
    usuario_mod='$this->nickname'
    WHERE codigo = '$codigo' returning id";
    $res_sql=$this->cnn->query('0', $sql);

    $vec=array();
    if(count($res_sql)>0)
    {
      $vec[0]['tipo'] = 1;
      $vec[0]['respuesta'] = "Se Cambio el Nombre.";
    }
    else
    {
      $vec[0]['tipo'] = 2;
      $vec[0]['respuesta'] = "No se Cambio.";
    }

    $response = new JsonResponse();
    $this->log->logs("********************Fin cambiarNombre TH***********************");
    return $response->setContent(json_encode($vec));
  }

  public function agregarPersona(Request $request)
  {
    $this->log->logs("********************Inicia agregarPersona TH***********************");
    $response = new JsonResponse();
    $content = $request->getContent();
    $jsonContent = json_decode($content, true);

    $cedula= (!empty($jsonContent['cedula'])) ? $jsonContent['cedula'] : null;
    $area= (!empty($jsonContent['area'])) ? $jsonContent['area'] : null;
    $info_cargo= (!empty($jsonContent['cargo'])) ? $jsonContent['cargo'] : null;
    $fechai_v= (!empty($jsonContent['fechai_v'])) ? $jsonContent['fechai_v'] : null;
    $fechaf_v= (!empty($jsonContent['fechaf_v'])) ? $jsonContent['fechaf_v'] : null;
    $es_vendedora= (!empty($jsonContent['es_vendedora'])) ? $jsonContent['es_vendedora'] : null;
    $enviar_correo= (!empty($jsonContent['enviar_correo'])) ? $jsonContent['enviar_correo'] : null;
    $correo= (!empty($jsonContent['correo'])) ? $jsonContent['correo'] : null;
    $periodoComp = (!empty($jsonContent['periodoComp']) && strpos($jsonContent['periodoComp'], '-') !== false) ? explode("-", $jsonContent['periodoComp'])[1] : null;
    $vec=array();

    $sql = "SELECT cedula FROM th_personal_administrativo WHERE cedula = '$cedula'";
    $res_sql=$this->cnn->query('0', $sql);
    if(count($res_sql) > 0)
    {
      $this->log->logs("********************Fin1 agregarPersona TH***********************");
      $vec[0]['tipo'] = 2;
      $vec[0]['respuesta'] = "La cedula Ingresada ya se Encuentra Registrada";
      return $response->setContent(json_encode($vec));
    }

    $arr_info_cargo = explode(",", $info_cargo);
    $id_cargo = $arr_info_cargo[0];
    $tipo = $arr_info_cargo[1];

    if ($tipo == 1)
    {
      $sql_asignaciones = "SELECT * from th_personal_administrativo_asignaciones where estado=0 and id_per_adm_cargo=$id_cargo";
      $res_sql_asignaciones=$this->cnn->query('0', $sql_asignaciones);
      if(count($res_sql_asignaciones) > 0)
      {
        $this->log->logs("********************Fin3 agregarPersona TH***********************");
        $vec[0]['tipo'] = 2;
        $vec[0]['respuesta'] = "El cargo ya se encuentra asignado";
        return $response->setContent(json_encode($vec));
      }
    }

    if($es_vendedora)
      $insert = "INSERT INTO th_personal_administrativo (cedula, id_per_adm_area,tipo,fechai,fechaf,periodo_compensatorio) VALUES ('$cedula', '$area','1', '$fechai_v', '$fechaf_v','$periodoComp') returning id";
    else
      $insert = "INSERT INTO th_personal_administrativo (cedula, id_per_adm_area,periodo_compensatorio) VALUES ('$cedula', '$area','$periodoComp') returning id";

    $res_insert=$this->cnn->query('0', $insert);
    if(count($res_insert)<=0)
    {
      $this->log->logs("********************Fin2 agregarPersona TH***********************");
      $vec[0]['tipo'] = 2;
      $vec[0]['respuesta'] = "!No se pudo Agregar, Intente Nuevamente \n Si el Error Persiste comuniquese con T.I";
      return $response->setContent(json_encode($vec));
    }

    $id_usuario=$res_insert[0]['id'];
    if($enviar_correo)
    {
      $sql_upd_correo = "UPDATE th_personal_administrativo set correo = '$correo' where id = '$id_usuario'";
      $this->cnn->query('0', $sql_upd_correo);
    }

    $insert_asignacion = "INSERT INTO th_personal_administrativo_asignaciones (id_per_adm_usuario_encargado, id_per_adm_cargo) VALUES ('$id_usuario', '$id_cargo') returning id";
    $res_insert_asignacion=$this->cnn->query('0', $insert_asignacion);
    if(count($res_insert_asignacion)<=0)
    {
      $this->log->logs("********************Fin4 agregarPersona TH***********************");
      $vec[0]['tipo'] = 2;
      $vec[0]['respuesta'] = "Usuario registrado, pero no se registro el cargo";
      return $response->setContent(json_encode($vec));
    }

    $vec[0]['tipo'] = 1;
    $vec[0]['respuesta'] = "Se Agrego Correctamente";
    $this->log->logs("********************Fin agregarPersona TH***********************");
    return $response->setContent(json_encode($vec));
  }

  public function consultaPersona(Request $request)
  {
    $this->log->logs("********************Inicia consultaPersona TH***********************");
    $response = new JsonResponse();
    $content = $request->getContent();
    $jsonContent = json_decode($content, true);
    $cedula=(!empty($jsonContent['cedula'])) ? $jsonContent['cedula'] : null;

    $sql = "SELECT t1.estado as estado, t2.nombre as area, t2.id,
    CASE WHEN t3.estado = 1 THEN NULL ELSE t3.id_per_adm_cargo END as id_cargo,
    CASE WHEN t3.estado = 1 THEN NULL ELSE t4.nombre END as cargo, t4.tipo, t1.tipo as tipo_usu, t1.fechai, t1.fechaf,
    COALESCE(tc.cantidad,0) AS cantidad, t1.correo, t1.periodo_compensatorio
    FROM th_personal_administrativo t1
    LEFT JOIN th_personal_administrativo_areas t2 ON t1.id_per_adm_area = t2.id
    LEFT JOIN th_personal_administrativo_asignaciones t3 ON t1.id = t3.id_per_adm_usuario_encargado
    LEFT JOIN th_cargos t4 ON t3.id_per_adm_cargo = t4.id
    LEFT JOIN (SELECT tc.id_cargo_permiso, count(*) AS cantidad FROM th_cargos tc WHERE tc.estado='0' GROUP BY tc.id_cargo_permiso) tc
    ON tc.id_cargo_permiso = t4.id
    WHERE t1.cedula = '$cedula' ORDER BY t3.fecha_sys DESC LIMIT 1";
    $res_sql = $this->cnn->query('0', $sql);

    $vec=array();
    if(count($res_sql)<=0)
    {
      $vec[0]['tipo'] = 2;
      $vec[0]['respuesta'] = "Hubo un error en el servicio";
      return $response->setContent(json_encode($vec));
    }

      $sql2 = "SELECT VINNOMBRE FROM VINCULADO WHERE VINCEDULA = '$cedula'";
      $res_sql2 = $this->cnn->query('5', $sql2);

      foreach($res_sql as $res)
      {
        $estado = $res['estado'];
        $area = $res['area'];
        $id = $res['id'];
        $id_cargo = $res['id_cargo'];
        $cargo = $res['cargo'];
        $tipo = $res['tipo'];
        $jefe = $res['cantidad'] > 0 ? 1 : 0;
        $tipo_usu = $res['tipo_usu'];
        $fechai = $res['fechai'];
        $fechaf = $res['fechaf'];
        $correo = $res['correo'];
        $periodoComp = $res['periodo_compensatorio'];
      }
      foreach($res_sql2 as $res2)
        $nombre = $res2['VINNOMBRE'];
      $vec[0]['tipo'] = 1;
      $vec[0]['nombre'] = $nombre;
      $vec[0]['estado'] = $estado;
      $vec[0]['area'] = $area;
      $vec[0]['id'] = $id;
      $vec[0]['id_cargo'] = null;
      if(!empty($id_cargo))
        $vec[0]['id_cargo'] = $id_cargo.",".$tipo.",".$jefe;
      $vec[0]['cargo'] = $cargo;
      $vec[0]['tipo_cargo'] = $tipo;
      $vec[0]['tipo_usu'] = $tipo_usu;
      $vec[0]['fechai'] = $fechai;
      $vec[0]['fechaf'] = $fechaf;
      $vec[0]['correo'] = $correo;
      $vec[0]['periodoComp'] = $periodoComp;

    $this->log->logs("********************Fin consultaPersona TH***********************");
    return $response->setContent(json_encode($vec));
  }

  public function modificar_persona(Request $request)
  {
    $this->log->logs("********************Inicia modificar_persona TH***********************");
    $response = new JsonResponse();
    $vec=array();
    $content = $request->getContent();
    $jsonContent = json_decode($content, true);

    $estado=(!empty($jsonContent['estado'])) ? $jsonContent['estado'] : null;
    $area=(!empty($jsonContent['area'])) ? $jsonContent['area'] : null;
    $cedula=(!empty($jsonContent['cedula'])) ? $jsonContent['cedula'] : null;
    $info_cargo=(!empty($jsonContent['cargo'])) ? $jsonContent['cargo'] : null;
    $fechai_v= (!empty($jsonContent['fechai_v'])) ? $jsonContent['fechai_v'] : null;
    $fechaf_v= (!empty($jsonContent['fechaf_v'])) ? $jsonContent['fechaf_v'] : null;
    $es_vendedora= (!empty($jsonContent['es_vendedora'])) ? $jsonContent['es_vendedora'] : null;
    $vendedora= (!empty($jsonContent['vendedora'])) ? $jsonContent['vendedora'] : null;
    $correo= (!empty($jsonContent['correo'])) ? $jsonContent['correo'] : null;
    $periodoComp = (!empty($jsonContent['periodoComp']) && strpos($jsonContent['periodoComp'], '-') !== false) ? explode("-", $jsonContent['periodoComp'])[1] : null;
    $numeroEstado = explode("-", $estado);

    $arr_info_cargo = explode(",", $info_cargo);
    $id_cargo = $arr_info_cargo[0];
    $tipo = $arr_info_cargo[1];

    if ($tipo == 1)
    {
      $sql_asignaciones = "SELECT * from th_personal_administrativo_asignaciones tpaa
      JOIN th_personal_administrativo tpa ON tpa.id = tpaa.id_per_adm_usuario_encargado AND tpa.cedula != '$cedula'
      where tpaa.estado=0 and tpaa.id_per_adm_cargo='$id_cargo'";
      $res_sql_asignaciones=$this->cnn->query('0', $sql_asignaciones);
      if(count($res_sql_asignaciones) > 0)
      {
        $this->log->logs("********************Fin1 modificar_persona TH***********************");
        $vec[0]['tipo'] = 2;
        $vec[0]['respuesta'] = "El cargo ya se encuentra asignado";
        return $response->setContent(json_encode($vec));
      }
    }

    if($es_vendedora)
    {
      $sql = "UPDATE th_personal_administrativo SET estado = '$numeroEstado[1]', id_per_adm_area = '$area', fecha_mod=now(),
      usuario_mod='$this->nickname', fechai='$fechai_v', fechaf='$fechaf_v', correo = '$correo', periodo_compensatorio = '$periodoComp' WHERE cedula = '$cedula' returning id";
    }
    else if(!$es_vendedora && $vendedora == "1")
    {
      $sql = "UPDATE th_personal_administrativo SET estado = '$numeroEstado[1]', id_per_adm_area = '$area', fecha_mod=now(),
      usuario_mod='$this->nickname', fechai=null, fechaf=null, tipo='0', correo = '$correo', periodo_compensatorio = '$periodoComp' WHERE cedula = '$cedula' returning id";
    }
    else
    {
      $sql = "UPDATE th_personal_administrativo SET estado = '$numeroEstado[1]', id_per_adm_area = '$area', fecha_mod=now(),
      usuario_mod='$this->nickname', correo = '$correo', periodo_compensatorio = '$periodoComp' WHERE cedula = '$cedula' returning id";
    }
    $res_sql=$this->cnn->query('0', $sql);

    if(count($res_sql)<=0)
    {
      $this->log->logs("********************Fin2 modificar_persona TH***********************");
      $vec[0]['tipo'] = 2;
      $vec[0]['respuesta']="No se pudo Actualizar, Intente Nuevamente";
      return $response->setContent(json_encode($vec));
    }
    $id_usuario=$res_sql[0]['id'];

    $sql_inactivar = "UPDATE TH_PERSONAL_ADMINISTRATIVO_ASIGNACIONES set estado = '1',fecha_mod=now(),
    usuario_mod='$this->nickname'
    where id_per_adm_usuario_encargado = '$id_usuario'";
    $res_sql_inactivar = $this->cnn->query('0', $sql_inactivar);

    /* if (!$res_sql_inactivar)
    {
      $this->log->logs("********************Fin4 modificar_persona TH***********************");
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No se pudo inactivar los registros anteriores, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    } */

    $insert_asignacion = "INSERT INTO th_personal_administrativo_asignaciones (id_per_adm_usuario_encargado, id_per_adm_cargo) VALUES ('$id_usuario', '$id_cargo') returning id";
    $res_insert_asignacion=$this->cnn->query('0', $insert_asignacion);
    if(count($res_insert_asignacion)<=0)
    {
      $this->log->logs("********************Fin3 modificar_persona TH***********************");
      $vec[0]['tipo'] = 2;
      $vec[0]['respuesta'] = "Usuario modificado, pero no se registro el cargo";
      return $response->setContent(json_encode($vec));
    }

    $vec[0]['tipo'] = 1;
    $vec[0]['respuesta'] = "Se Agrego Correctamente";
    $this->log->logs("********************Fin modificar_persona TH***********************");
    return $response->setContent(json_encode($vec));
  }

  public function consultaLNR(Request $request)
  {
    $this->log->logs("********************Inicia consultaLNR TH***********************");
    $content = $request->getContent();
    $jsonContent = json_decode($content, true);
    $cedula=(!empty($jsonContent['cedula'])) ? $jsonContent['cedula'] : null;

    $sql = "select dias, estado from th_dias_lnr tdl where estado=0 and cedula='$cedula'";
    $res_sql = $this->cnn->query('0', $sql);

    $vec=array();
    $vec[0]['tipo'] = 1;
    $vec[0]['dias'] = "3";
    $vec[0]['estado'] = "2";//no existe
    if(count($res_sql)>0)
    {
      foreach($res_sql as $res)
      {
        $vec[0]['dias'] = $res['dias'];
        $vec[0]['estado'] = $res['estado'];
      }
    }

    $response = new JsonResponse();
    $this->log->logs("********************Fin consultaLNR TH***********************");
    return $response->setContent(json_encode($vec));
  }

  public function modificarLNR(Request $request)
  {
    $this->log->logs("********************Inicia modificarLNR TH***********************");
    $response = new JsonResponse();
    $vec=array();
    $vec[0]['tipo'] = 2;
    $vec[0]['respuesta'] = "Los Dias de Licencia no remunerada no se pudieron modificar, intente nuevamente";
    $content = $request->getContent();
    $jsonContent = json_decode($content, true);

    $estado=(!empty($jsonContent['estado'])) ? $jsonContent['estado'] : null;
    $estado2=(!empty($jsonContent['estado2'])) ? $jsonContent['estado2'] : null;
    $cedula=(!empty($jsonContent['cedula'])) ? $jsonContent['cedula'] : null;
    $dias=(!empty($jsonContent['dias'])) ? $jsonContent['dias'] : null;

    if($estado==2)
    {
      $insert_asignacion = "INSERT INTO th_dias_lnr (cedula, dias, estado, usuario_mod) VALUES ('$cedula', '$dias', $estado2, '$this->nickname') returning id";
      $res_insert_asignacion=$this->cnn->query('0', $insert_asignacion);
      if(count($res_insert_asignacion)>0)
      {
        $vec[0]['tipo'] = 1;
        $vec[0]['respuesta'] = "Dias de Licencia no remunerada modificados";
      }
      $this->log->logs("********************Fin1 modificarLNR TH***********************");
      return $response->setContent(json_encode($vec));
    }

    $sql = "UPDATE th_dias_lnr SET estado = $estado2, dias = '$dias', fecha_mod=now(),
    usuario_mod='$this->nickname' WHERE cedula = '$cedula' returning id";
    $res_sql=$this->cnn->query('0', $sql);

    if(count($res_sql)<=0)
    {
      $this->log->logs("********************Fin2 modificarLNR TH***********************");
      return $response->setContent(json_encode($vec));
    }

    $vec[0]['tipo'] = 1;
    $vec[0]['respuesta'] = "Dias de Licencia no remunerada modificados";
    $this->log->logs("********************Fin modificarLNR TH***********************");
    return $response->setContent(json_encode($vec));
  }

  public function consultaLCumple(Request $request)
  {
    $this->log->logs("********************Inicia consultaLCumple TH***********************");
    $content = $request->getContent();
    $jsonContent = json_decode($content, true);
    $cedula=(!empty($jsonContent['cedula'])) ? $jsonContent['cedula'] : null;

    $sql = "SELECT anio, estado from th_habil_cumple where cedula='$cedula'";
    $res_sql = $this->cnn->query('0', $sql);

    $vec=array();
    $vec[0]['tipo'] = 1;
    $vec[0]['anio'] = "";
    $vec[0]['estado'] = "2";//no existe
    if(count($res_sql)>0)
    {
      foreach($res_sql as $res)
      {
        $vec[0]['anio'] = $res['anio'];
        $vec[0]['estado'] = $res['estado'];
      }
    }

    $response = new JsonResponse();
    $this->log->logs("********************Fin consultaLCumple TH***********************");
    return $response->setContent(json_encode($vec));
  }

  public function modificarLCumple(Request $request)
  {
    $this->log->logs("********************Inicia modificarLCumple TH***********************");
    $response = new JsonResponse();
    $vec=array();
    $vec[0]['tipo'] = 2;
    $vec[0]['respuesta'] = "La informacion de Licencia no se pudo modificar, intente nuevamente";
    $content = $request->getContent();
    $jsonContent = json_decode($content, true);

    $estado=(!empty($jsonContent['estado'])) ? $jsonContent['estado'] : null;
    $estado2=(!empty($jsonContent['estado2'])) ? $jsonContent['estado2'] : null;
    $cedula=(!empty($jsonContent['cedula'])) ? $jsonContent['cedula'] : null;
    $anio=(!empty($jsonContent['anio'])) ? $jsonContent['anio'] : null;

    if($estado==2)
    {
      $insert_asignacion = "INSERT INTO th_habil_cumple (cedula, anio, estado, usuario_mod) VALUES ('$cedula', '$anio', $estado2, '$this->nickname') returning id";
      $res_insert_asignacion=$this->cnn->query('0', $insert_asignacion);
      if(count($res_insert_asignacion)>0)
      {
        $vec[0]['tipo'] = 1;
        $vec[0]['respuesta'] = "Informacion modificada";
      }
      $this->log->logs("********************Fin1 modificarLCumple TH***********************");
      return $response->setContent(json_encode($vec));
    }

    $sql = "UPDATE th_habil_cumple SET estado = $estado2, anio = '$anio', fecha_mod=now(),
    usuario_mod='$this->nickname' WHERE cedula = '$cedula' returning id";
    $res_sql=$this->cnn->query('0', $sql);

    if(count($res_sql)<=0)
    {
      $this->log->logs("********************Fin2 modificarLCumple TH***********************");
      return $response->setContent(json_encode($vec));
    }

    $vec[0]['tipo'] = 1;
    $vec[0]['respuesta'] = "Informacion modificada";
    $this->log->logs("********************Fin modificarLCumple TH***********************");
    return $response->setContent(json_encode($vec));
  }

  public function generar_reporte_jefes_th()//metodo que generar en excel la estructura de los jefes de th
  {
    $this->log->logs("********************Inicia generar_reporte_jefes_th TH***********************");
    $response = new JsonResponse();
    $respuesta = array();
    $vec=array();
    $vec_cargos=array();
    $cont=0;
    $cont2=0;
    $id_cargo=0;

    $sql = "SELECT tc.id,tc.nombre as nombreCargo,tpaa2.nombre as nombreArea,tpa.cedula, p.nombres || ' ' || p.apellido1 as nombrePersona,
    tpaa2.id as area, tc.id_cargo_permiso,'1' as tipo
    FROM th_cargos tc
    left JOIN th_personal_administrativo_asignaciones tpaa ON tc.id = tpaa.id_per_adm_cargo
    left JOIN th_personal_administrativo tpa ON tpaa.id_per_adm_usuario_encargado = tpa.id
    left join th_personal_administrativo_areas tpaa2 on tc.id_per_adm_area = tpaa2.id
    left join personas p on p.documento=tpa.cedula
    WHERE tpa.estado = 0 AND tpaa.estado = 0 AND tc.estado = 0 and tpaa2.estado = 0
    union all
    select '0' as id,'ASESORA' as nombreCargo,'COMERCIAL' as nombreArea,c.prs_documento as cedula, p.nombres || ' ' || p.apellido1 as nombrePersona,
    c2.UBCNTRTRIO_CODIGO_COMPUESTO__1 as area, '34' as id_cargo_permiso,'2' as tipo
    from gamble_70.contratosventa c
    join personas p on p.documento=c.prs_documento
    join controlhorariopersonas c2 on c2.login='CV'||c.prs_documento
    join (SELECT max(cal_dia) as cal_dia,login from CONTROLHORARIOPERSONAS group by login) cdz on cdz.login='CV'||c.prs_documento and cdz.cal_dia=c2.cal_dia
    where c.fechafinal is null AND grpvtas_codigo IN (5,36,58,4,63)
    order by id desc";
    $result = $this->cnn->query('0', $sql);
    foreach ($result as $row)
    {
      $nombre_cargo = trim($row['nombrecargo']);
      $nombre_area = trim($row['nombrearea']);
      $cedula = trim($row['cedula']);
      $nombre_persona = trim($row['nombrepersona']);
      $area = trim($row['area']);
      $tipo = trim($row['tipo']);

      $vec[$cont]['id'] = trim($row['id']);
      $vec[$cont]['nombre_cargo'] = $nombre_cargo;
      $vec[$cont]['nombre_area'] = $nombre_area;
      $vec[$cont]['cedula'] = $cedula;
      $vec[$cont]['nombre_persona'] = $nombre_persona;
      $vec[$cont]['area'] = $area;
      $vec[$cont]['tipo'] = $tipo;

      if($cont==0 || trim($row['id']) != $id_cargo)
      {
        $vec_cargos[$cont2]['id'] = trim($row['id']);
        $vec_cargos[$cont2]['nombre_cargo'] = $nombre_cargo;
        $vec_cargos[$cont2]['nombre_area'] = $nombre_area;
        $vec_cargos[$cont2]['id_cargo_permiso'] = trim($row['id_cargo_permiso']);
        $cont2++;
      }

      $id_cargo = trim($row['id']);
      unset($nombre_cargo,$nombre_area,$cedula,$nombre_persona,$area,$tipo);
      $cont++;
    }

    foreach ($vec_cargos as $cargos)
    {
      $id_cargo_permiso = $cargos['id_cargo_permiso'];
      if($cargos['id_cargo_permiso']!=0)
      {
        $cargos_nombres=array();
        $val_cargos = true;
        while ($val_cargos == true)
        {
          $key = array_search($id_cargo_permiso, array_column($vec_cargos, 'id'));
          if ($key!== false)
          {
            $id_cargo_permiso = $vec_cargos[$key]['id_cargo_permiso'];
            if($id_cargo_permiso == 0)
              $val_cargos = false;
            $cargos_nombres[] = $vec_cargos[$key]['nombre_cargo'];
          }
          else
            $val_cargos = false;
          unset($key);
        }
        if(count($cargos_nombres)>0)
          $this->buscar_elemento($vec, $cargos['id'], implode(",", $cargos_nombres));
      }
    }

    if (empty($vec))
    {
      $this->log->logs("********************Fin1 generar_reporte_jefes_th TH***********************");
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No hay datos, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }

    // echo "ok";
    // die();

    //generar el archivo excel
    $fecha_a = date('Y_m_d_H_i_s');
    $dir = $this->ruta . "/public/uploads/talento_humano/archivos/" . $this->env."/";
    $archivo = $this->ruta . "/public/uploads/talento_humano/archivos/". $this->env ."/reporte_grafo_th_" . $fecha_a . ".xlsx";

    $filesystem = new Filesystem();
    $this->log->logs("archivo ".$archivo);
    if (!is_dir($dir))
    {
      $this->filesystem->mkdir(Path::normalize($dir),0777);
      $filesystem->touch($archivo);
      $filesystem->chmod($archivo, 0777);
    }
    if (!file_exists($archivo))
    {
      $filesystem->touch($archivo);
      $filesystem->chmod($archivo, 0777);
    }

    $archivo2 = $this->ip."/uploads/talento_humano/archivos/" . $this->env."/reporte_grafo_th_" . $fecha_a . ".xlsx";
    $archivo1 = $this->ruta . "/public/uploads/talento_humano/archivos/". $this->env ."/reporte_grafo_th_" . $fecha_a . ".xlsx";

    //ARMANDO MATRIZ PARA GENERAR EXCEL CON EXPORTCONTROLLER
    $vec_export[0][0] = "CARGO";
    $vec_export[0][1] = "AREA";
    $vec_export[0][2] = "CEDULA";
    $vec_export[0][3] = "NOMBRES";
    $vec_export[0][4] = "CARGOS JEFES";

    for ($i = 0; $i < count($vec); $i++)
    {
      $vec_export[$i+1][0]=trim($vec[$i]['nombre_cargo']);
      $vec_export[$i+1][1]=trim($vec[$i]['nombre_area']);
      $vec_export[$i+1][2]=trim($vec[$i]['cedula']);
      $vec_export[$i+1][3]=trim($vec[$i]['nombre_persona']);
      if (isset($vec[$i]['nombres_cargos']))
        $vec_export[$i+1][4]=trim($vec[$i]['nombres_cargos']);
      else
        $vec_export[$i+1][4]="";
    }

    $respuesta_excel = $this->excel->excel($vec_export, null, $archivo1);
    if (empty($respuesta_excel))
    {
      $this->log->logs("********************Fin2 solicitudes_reporte TH***********************");
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No se genero el archivo, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }
    $res_excel = json_decode($respuesta_excel, true);
    $this->log->logs("res_excel ",array($res_excel));

    $respuesta["code"] = 1;
    $respuesta["datos"] = $archivo2;
    $respuesta["datos2"] = $archivo1;
    $this->log->logs("********************Fin generar_reporte_jefes_th TH***********************");
    return $response->setContent(json_encode($respuesta));
  }

  public function solicitudes_reporte_asesoras_sin_asignacion(Request $request)
  {
    $this->log->logs("********************Inicia solicitudes_reporte_asesoras_sin_asignacion TH***********************");
    $response = new JsonResponse();
    $fecha_a = date('Y_m_d_H_i_s');
    $dir = $this->ruta . "/public/uploads/talento_humano/archivos/" . $this->env."/";
    $archivo = $this->ruta . "/public/uploads/talento_humano/archivos/". $this->env ."/reporte_th_solicitudes_asesoras_sin_asignacion_" . $fecha_a . ".xlsx";

    $filesystem = new Filesystem();
    $this->log->logs("archivo ".$archivo);
    if (!is_dir($dir))
    {
      $this->filesystem->mkdir(Path::normalize($dir),0777);
      $filesystem->touch($archivo);
      $filesystem->chmod($archivo, 0777);
    }
    if (!file_exists($archivo))
    {
      $filesystem->touch($archivo);
      $filesystem->chmod($archivo, 0777);
    }

    $vec_gamble_aux = $this->array_solicitudes_excel_asesoras_sin_asignacion();
    $this->log->logs("vec_gamble_aux ",array($vec_gamble_aux));
    $vec_gamble = $vec_gamble_aux[0];
    $arr_cedulas = $vec_gamble_aux[1];
    $arr_pdv = $vec_gamble_aux[2];

    if (empty($vec_gamble))
    {
      $this->log->logs("********************Fin1 solicitudes_reporte_asesoras_sin_asignacion TH***********************");
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No hay Asesoras sin asignacion de dias de compensacion";
      return $response->setContent(json_encode($respuesta));
    }
    $archivo2 = $this->ip."/uploads/talento_humano/archivos/" . $this->env."/reporte_th_solicitudes_asesoras_sin_asignacion_" . $fecha_a . ".xlsx";
    $archivo1 = $this->ruta . "/public/uploads/talento_humano/archivos/". $this->env ."/reporte_th_solicitudes_asesoras_sin_asignacion_" . $fecha_a . ".xlsx";

    $vec_manager = $this->array_solicitudes_manager_excel_cedula(implode(', ', $arr_cedulas));
    $vec_gamble_pdv = $this->array_solicitudes_gamble_excel_pdv(implode(', ', $arr_pdv));

    for ($i = 0; $i < count($vec_gamble); $i++)
    {
      $vec_gamble[$i][15] = "";
      $vec_gamble[$i][17] = "";
      for ($j = 0; $j < count($vec_manager); $j++)
      {
        if($vec_gamble[$i][14]==$vec_manager[$j][0])
        {
          $vec_gamble[$i][15] = $vec_manager[$j][1];
          break;
        }
      }

      for ($j = 0; $j < count($vec_gamble_pdv); $j++)
      {
        if($vec_gamble[$i][16]==$vec_gamble_pdv[$j][0])
        {
          $vec_gamble[$i][17] = $vec_gamble_pdv[$j][1];
          break;
        }
      }
    }

    //ARMANDO MATRIZ PARA GENERAR EXCEL CON EXPORTCONTROLLER
    $vec_export[0][0] = "ID SOLICITUD";
    $vec_export[0][1] = "TIPO SOLICITUD";
    $vec_export[0][2] = "FECHA REGISTRO SOLICITUD";
    $vec_export[0][3] = "ESTADO";
    $vec_export[0][4] = "CEDULA COORDINADOR";
    $vec_export[0][5] = "NOMBRE COORDINADOR";
    $vec_export[0][6] = "AREA";
    $vec_export[0][7] = "TIPO";
    $vec_export[0][8] = "ASESORA";
    $vec_export[0][9] = "NOMBRE ASESORA";
    $vec_export[0][10] = "PDV";
    $vec_export[0][11] = "NOMBRE PDV";
    $vec_export[0][12] = "TURNO";

    for ($i = 0; $i < count($vec_gamble); $i++)
    {
      $vec_export[$i+1][0]=trim($vec_gamble[$i][0]);
      $vec_export[$i+1][1]=trim($vec_gamble[$i][1]);
      $vec_export[$i+1][2]=trim($vec_gamble[$i][2]);
      $vec_export[$i+1][3]=trim($vec_gamble[$i][3]);
      $vec_export[$i+1][4]=trim($vec_gamble[$i][4]);
      $vec_export[$i+1][5]=trim($vec_gamble[$i][5]);
      $vec_export[$i+1][6]=trim($vec_gamble[$i][6]);
      $vec_export[$i+1][7]=trim($vec_gamble[$i][7]);
      $vec_export[$i+1][8]=trim($vec_gamble[$i][14]);
      $vec_export[$i+1][9]=trim($vec_gamble[$i][15]);
      $vec_export[$i+1][10]=trim($vec_gamble[$i][16]);
      $vec_export[$i+1][11]=trim($vec_gamble[$i][17]);
      $vec_export[$i+1][12]=trim($vec_gamble[$i][18]);
    }

    $respuesta_excel = $this->excel->excel($vec_export, null, $archivo1);
    if (empty($respuesta_excel))
    {
      $this->log->logs("********************Fin2 solicitudes_reporte_asesoras_sin_asignacion TH***********************");
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No se genero el archivo, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }
    $res_excel = json_decode($respuesta_excel, true);
    $this->log->logs("res_excel ",array($res_excel));

    $respuesta["code"] = 1;
    $respuesta["datos"] = $archivo2;
    $respuesta["datos2"] = $archivo1;
    $this->log->logs("********************Fin solicitudes_reporte_asesoras_sin_asignacion TH***********************");
    return $response->setContent(json_encode($respuesta));
  }

  public function usuariosActivosManager()//consultar los usuarios activos en manager
  {
    $this->log->logs("********************Inicia usuariosActivosManager TH***********************");
    $response = new JsonResponse();
    $vec = array();
    $cedulas = array();

    $fechaHoy = date("d/m/Y");
    $manager = "SELECT v.VINCEDULA AS CEDULA, v.VINNOMBRE AS NOMBRE FROM VINCULADO v, NMCARGO m, NMEMPLEADO n, PARAMETRO p, MNGDNO d
    WHERE n.NEMDESTINO=d.DNOCODIGO AND p.PARCODIGO=n.NEMTPCONTR AND v.VINCEDULA=n.NEMCEDULA AND m.NCRCODIGO=n.NEMCARGO
    AND v.VINFECRET > TO_DATE('$fechaHoy', 'DD/MM/YYYY') AND p.PARCODIGO in('NM0901', 'NM0902', 'NM0905') AND n.NEMCARGO not in('3502', '3503')
    AND v.VINACTIVO = '1' AND v.VINEMPLEA = '1' ORDER BY v.VINCEDULA ASC";
    $resUsuariosManager = $this->cnn->query('5', $manager);

    foreach($resUsuariosManager as $resUsuarioManager)
    {
      array_push($vec, array("cedula" => trim($resUsuarioManager['CEDULA']), "nombre" => trim($resUsuarioManager['NOMBRE'])));
      $cedulas[] = trim($resUsuarioManager['CEDULA']);
    }
    $strCedulas = implode("','", $cedulas);

    $gamble = "SELECT DISTINCT cv.PRS_DOCUMENTO as cedula, CONCAT (CONCAT(p.nombres,' '),p.apellido1) as nombre FROM CONTRATOSVENTA cv
    JOIN personas p ON p.DOCUMENTO = cv.PRS_DOCUMENTO
    WHERE cv.FECHAFINAL IS NULL AND cv.PRS_DOCUMENTO NOT IN ('1001','$strCedulas')";
    $resUsuariosGamble=$this->cnn->query('2', $gamble);

    foreach($resUsuariosGamble as $resUsuarioGamble)
      array_push($vec, array("cedula" => trim($resUsuarioGamble['CEDULA']), "nombre" => trim($resUsuarioGamble['NOMBRE'])));

    $sqlSgc = "SELECT tpa.cedula, CONCAT (CONCAT(p.nombres,' '),p.apellido1) as nombre FROM th_personal_administrativo tpa
    JOIN th_personal_administrativo_asignaciones tpaa ON tpaa.id_per_adm_usuario_encargado = tpa.id
    JOIN th_cargos tc ON tc.id = tpaa.id_per_adm_cargo
    JOIN personas p ON p.documento = tpa.cedula
    WHERE tpa.estado = 0 AND tpaa.estado = 0 AND tc.estado = 0 AND tc.id IN (97)";
    $resSgc=$this->cnn->query('0', $sqlSgc);

    foreach($resSgc as $res)
      array_push($vec, array("cedula" => trim($res['cedula']), "nombre" => trim($res['nombre'])));

    $this->log->logs("********************Fin usuariosActivosManager TH***********************");
    return $response->setContent(json_encode($vec));
  }

  public function consultaSuspencionesXUsuarios(Request $request)//consultar las suspensiones por usuario
  {
    $this->log->logs("********************Inicia consultaSuspencionesXUsuarios TH***********************");
    $response = new JsonResponse();
    $vec = array();

    $content = $request->getContent();
    $jsonContent = json_decode($content, true);
    $cedula=(!empty($jsonContent['cedula'])) ? $jsonContent['cedula'] : null;

    $sql = "SELECT id, fechai, fechaf, dias, estado FROM th_suspensiones ts WHERE usuario = '$cedula' ORDER BY id";
    $resSuspensiones = $this->cnn->query('0', $sql);

    foreach($resSuspensiones as $resSuspension)
      array_push($vec, array("id" => trim($resSuspension['id']), "fechai" => trim($resSuspension['fechai']), "fechaf" => trim($resSuspension['fechaf']), "dias" => trim($resSuspension['dias']), "estado" => trim($resSuspension['estado'])));

    $this->log->logs("********************Fin consultaSuspencionesXUsuarios TH***********************");
    return $response->setContent(json_encode($vec));
  }

  public function registrarSuspension(Request $request, ServiceEmail $email)//registrar suspension de usuario
  {
    $this->log->logs("********************Inicia registrarSuspension TH***********************");
    $responseIni = $this->initialize();
    if ($responseIni) return $responseIni;

    $response = new JsonResponse();
    $respuesta = array();
    $respuesta['code'] = 0;
    $fechaHoy = date("Y-m-d");

    $content = $request->getContent();
    $jsonContent = json_decode($content, true);
    $cedula=(!empty($jsonContent['cedula'])) ? $jsonContent['cedula'] : null;
    $fechai=(!empty($jsonContent['fechai'])) ? $jsonContent['fechai'] : null;
    $diasSuspension=(!empty($jsonContent['diasSuspension'])) ? $jsonContent['diasSuspension'] : null;
    $id=(!empty($jsonContent['id'])) ? $jsonContent['id'] : null;
    $estado=(!empty($jsonContent['estado'])) ? $jsonContent['estado'] : null;
    $fechaf = $this->calcularFechaFinal($fechai, $diasSuspension);

    $usuCv = "CV".$cedula;
    $usuCp = "CP".$cedula;
    $tipoUsu = 1;
    $sqlValTipoUsu = "SELECT r.role, r2.role FROM gamble_70.rolesusuarios r
    JOIN gamble_70.rolesusuarios r2 ON r2.loginusr = '$usuCv' AND r2.ROLE = 'ROL_VENTAENLINEA2'
    WHERE r.loginusr = '$usuCp' AND r.ROLE = 'ROL_VENPAGPREM'";
    $resultValTipoUsu = $this->cnn->query('0', $sqlValTipoUsu);
    if (count($resultValTipoUsu) > 0) $tipoUsu = 2;

    if(empty($id))
      $sql = "INSERT INTO th_suspensiones (usuario, fechai, fechaf, dias, usuario_reg, tipo_usu)
        VALUES ('$cedula','$fechai','$fechaf[0]','$diasSuspension','$this->nickname','$tipoUsu') RETURNING id";
    else
    {
      if(empty($estado)) $estado = 0;
      $sql = "UPDATE th_suspensiones SET fechai = '$fechai', fechaf = '$fechaf[0]', dias = '$diasSuspension', estado = '$estado',
        usuario_mod = '$this->nickname', fecha_mod = NOW(), tipo_usu = '$tipoUsu' WHERE id = '$id' RETURNING id";
    }

    $resSuspensiones = $this->cnn->query('0', $sql);
    if (count($resSuspensiones) <= 0)
    {
      $respuesta["datos"] = "No se pudo registrar, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }

    $arr_bloqueo = array();
    array_push($arr_bloqueo, array($cedula,$this->nickname));
    if($fechaHoy == $fechai && (empty($estado) || (!empty($estado) && $estado == "0"))) $this->bloqueoUsuarios($arr_bloqueo);

    $gamble = "SELECT un.trtrio_codigo_compuesto_de,t.NOMBRE, SUBSTR(u.LOGINUSR, 3) AS supervisor
    FROM usuarios u, rolesusuarios r, contratopersonas cp, ubicacionnegocios un, territorios t, (SELECT DISTINCT cv.PRS_DOCUMENTO, cv.UBCNTRTRIO_CODIGO_COMPUESTO_DE FROM CONTRATOSVENTA cv
    JOIN personas p ON p.DOCUMENTO = cv.PRS_DOCUMENTO WHERE cv.FECHAFINAL IS NULL AND cv.PRS_DOCUMENTO = '$cedula') usu
    WHERE usu.UBCNTRTRIO_CODIGO_COMPUESTO_DE = un.trtrio_codigo_compuesto_de and
    u.loginusr=r.loginusr and u.estado='A' and u.loginusr=cp.login and cp.fechafinal is null
    AND cp.ubcntrtrio_codigo_compuesto_de=un.trtrio_codigo_compuesto_de and cp.ubcneg_trtrio_codigo=un.trtrio_codigo and t.codigo=un.trtrio_codigo_compuesto_de
    AND r.role='ROL_SUPERVISOR' and un.trtrio_codigo not in('2948','1078') and cp.login not in ('CP40342853')";
    $resUsuarioGamble=$this->cnn->query('2', $gamble);
    if (count($resUsuarioGamble) > 0)
    {
      $cedJefe = $resUsuarioGamble[0]["SUPERVISOR"];
      $sqlCorreoJefe = "SELECT correo FROM th_personal_administrativo tpa WHERE cedula = '$cedJefe'";
    }
    else
    {
      $sqlCorreoJefe = "SELECT tc.id_cargo_permiso, tc2.nombre, tpa2.correo FROM th_personal_administrativo tpa
      JOIN th_personal_administrativo_asignaciones tpaa ON tpaa.id_per_adm_usuario_encargado = tpa.id AND tpaa.estado = '0'
      JOIN th_cargos tc ON tc.id = tpaa.id_per_adm_cargo AND tc.estado = '0'
      JOIN th_cargos tc2 ON tc2.id = tc.id_cargo_permiso
      JOIN th_personal_administrativo_asignaciones tpaa2 ON tpaa2.id_per_adm_cargo = tc2.id AND tpaa2.estado = '0'
      JOIN th_personal_administrativo tpa2 ON tpa2.id = tpaa2.id_per_adm_usuario_encargado and tpa2.estado='0'
      WHERE tpa.cedula = '$cedula'";
    }
    $resCorreoJefe=$this->cnn->query('0', $sqlCorreoJefe);

    if(empty($estado) || (!empty($estado) && $estado == "0"))
    {
      $destinatarios[0]="acespedes@consuerte.com.co";
      $destinatarios[1]="ajquebradab@consuerte.com.co";
      $destinatarios[2]="asisnomina@consuerte.com.co";

      $sql_manager = "SELECT VINEMAIL, VINNOMBRE from VINCULADO where VINCEDULA='$cedula'";
      $result_manager = $this->cnn->query('5', $sql_manager);
      $nombreU = "";
      if (count($result_manager) > 0)
      {
        $destinatarios[3] = $result_manager[0]['VINEMAIL'];
        $nombreU = $result_manager[0]['VINNOMBRE'];
      }
      if (count($resCorreoJefe) > 0) $destinatarios[4] = $resCorreoJefe[0]["correo"];

      $asunto = "Suspensión de contrato de trabajo";
      $informacion = "Señor(a) $nombreU identificado con CC $cedula.".
      "<br>De acuerdo con el proceso disciplinario, se realiza suspensión de contrato de trabajo sin remuneración desde el $fechai hasta el $fechaf[0], un total de $diasSuspension día(s).".
      "<br>La reincorporación está programada para el $fechaf[1].".
      "<br><br>Si necesita más información, comunicarse con el area de Talento Humano.";
      $res_correo = $this->enviar_correo($email,$asunto,$informacion,$destinatarios,"SUSPENSIÓN DE CONTRATO DE TRABAJO");
      $this->log->logs("r correo",$res_correo);
    }

    $respuesta['code'] = 1;
    $respuesta['respuesta'] = "Informacion registrada";
    $this->log->logs("********************Fin registrarSuspension TH***********************");
    return $response->setContent(json_encode($respuesta));
  }

  public function bloqueoDesbloqueoUsuariosSuspendidos()
  {
    $this->log->logs("********************Inicia bloqueoDesbloqueoUsuariosSuspendidos TH***********************");
    $arrUsuariosBloquear = array();
    $arrUsuariosDesbloquear = array();

    $sql = "SELECT DISTINCT usuario, usuario_reg FROM th_suspensiones ts WHERE fechai = date(now()) AND estado = '0'";
    $resUsuariosSuspender = $this->cnn->query('0', $sql);
    foreach ($resUsuariosSuspender as $res) $arrUsuariosBloquear[] = array(trim($res["usuario"]),trim($res["usuario_reg"]));

    $sql = "SELECT DISTINCT usuario FROM th_suspensiones ts WHERE date(fechaf + INTERVAL '1 day') = date(now()) AND estado = '0'";
    $resUsuariosDesbloquear = $this->cnn->query('0', $sql);
    foreach ($resUsuariosDesbloquear as $res) $arrUsuariosDesbloquear[] = trim($res["usuario"]);

    $usuariosDesbloquear = "'" . implode("','", $arrUsuariosDesbloquear) . "'";
    $this->bloqueoUsuarios($arrUsuariosBloquear);
    $this->desbloqueoUsuarios($usuariosDesbloquear);

    $this->log->logs("********************Fin bloqueoDesbloqueoUsuariosSuspendidos TH***********************");
    return array("status" =>0,"message"=>"Proceso Exitoso");
  }

  public function consultaCronogramaVacaciones()
  {
    $this->log->logs("********************Inicia consultaCronogramaVacaciones TH***********************");
    $response = new JsonResponse();
    $respuesta = array();
    $usuarios = array();
    $cedulas = array();
    $cedulas2 = array();
    $zonas = array();
    $ccostos = [];
    $cedulasReemplazos = array();
    $respuesta['code'] = 0;
    $count_cc = 0;
    $sql_aux = "";

    $cedula = substr($this->nickname, 2);
    if ($cedula == "1121966060" || $cedula == "1121893580" || $cedula == "1006820220")
    {
      $id = "5";
      $sql_aux = "tc.id_cargo_permiso = '$id'";
    }
    else
    {
      $sqlValProgVacaciones = "SELECT tc.id, tc.nombre, tc.id_per_adm_area FROM th_personal_administrativo tpa
      JOIN th_personal_administrativo_asignaciones tpaa ON tpaa.id_per_adm_usuario_encargado = tpa.id AND tpaa.estado = '0'
      JOIN th_cargos tc ON tc.id = tpaa.id_per_adm_cargo AND tc.estado = '0' AND tc.id IN
      (SELECT id_cargo_permiso FROM th_cargos tc WHERE id_cargo_permiso NOT IN (34) AND estado = 0 GROUP BY id_cargo_permiso)
      WHERE tpa.cedula = '$cedula'";
      $resValProgVacaciones = $this->cnn->query('0', $sqlValProgVacaciones);
      if(count($resValProgVacaciones)<=0)
      {
        $respuesta["datos"] = "Usted no puede programar vacaciones, comuniquese con TH";
        return $response->setContent(json_encode($respuesta));
      }

      $id = $resValProgVacaciones[0]["id"];
      $id_area = $resValProgVacaciones[0]["id_per_adm_area"];
      $sql_aux = "(tc.id_cargo_permiso = '$id' OR tc.id = '$id')";
      if ($id == 79)
        $sql_aux = "tc.id_per_adm_area = $id_area";
    }
    $directorTH = false;

    /* $sqlUsuariosVacaciones = "SELECT tpa.cedula, concat(p.nombres, ' ', p.apellido1, ' ', p.apellido2) as nombre, tpa.periodo_compensatorio as comp, concat(tpa.id_per_adm_area, ' - ', tpaa2.nombre) as zona FROM th_cargos tc
    JOIN th_personal_administrativo_asignaciones tpaa ON tpaa.id_per_adm_cargo = tc.id AND tpaa.estado = '0'
    JOIN th_personal_administrativo tpa ON tpa.id = tpaa.id_per_adm_usuario_encargado AND tpa.estado = '0'
    JOIN th_personal_administrativo_areas tpaa2 ON tpaa2.id = tpa.id_per_adm_area AND tpaa2.estado = '0'
    LEFT JOIN personas p ON p.documento = tpa.cedula
    WHERE $sql_aux AND tc.estado = '0' AND tc.id NOT IN (37)"; */
    $sqlUsuariosVacaciones = "SELECT tpa.cedula, tpa.periodo_compensatorio as comp, concat(tpa.id_per_adm_area, ' - ', tpaa2.nombre) as zona FROM th_cargos tc
    JOIN th_personal_administrativo_asignaciones tpaa ON tpaa.id_per_adm_cargo = tc.id AND tpaa.estado = '0'
    JOIN th_personal_administrativo tpa ON tpa.id = tpaa.id_per_adm_usuario_encargado AND tpa.estado = '0'
    JOIN th_personal_administrativo_areas tpaa2 ON tpaa2.id = tpa.id_per_adm_area AND tpaa2.estado = '0'
    WHERE $sql_aux AND tc.estado = '0' AND tc.id NOT IN (37)";
    $resUsuariosVacaciones = $this->cnn->query('0', $sqlUsuariosVacaciones);
    if(count($resUsuariosVacaciones)<=0)
    {
      $respuesta["datos"] = "No tiene personas asignadas para programar vacaciones, comuniquese con TH";
      return $response->setContent(json_encode($respuesta));
    }

    foreach($resUsuariosVacaciones as $res)
    {
      array_push($usuarios, array("cedula" => $res['cedula'], "nombre" => "", "periodos" => 0, "rangoFecPtes" => "", "fecha_inicio" => "", "fecha_fin" => "", "reemplazo" => "", "zona" => $res['zona'], "ccostos" => "", "perAceptados" => "0", "jornada" => "1", "fecPteInicio" => "", "fecPteFin" => ""));
      $cedulas[] = $res["cedula"];
      $count_cc++;
    }

    $fecha_actual = date('Y-m-d');
    $cedulas_separadas_por_comas = "'" . implode("','", $cedulas) . "'";
    $sql_nombres_manager = "SELECT VINCEDULA, VINNOMBRE, to_char(VINFECRET, 'YYYY-MM-DD') AS VINFECRET FROM vinculado WHERE VINCEDULA IN ($cedulas_separadas_por_comas)";
    $res_sql_nombres_manager = $this->cnn->query('5', $sql_nombres_manager);
    foreach($res_sql_nombres_manager as $res)
    {
      $cedula = $res['VINCEDULA'];
      $nombre = $res['VINNOMBRE'];
      $fecha_ret = $res['VINFECRET'];

      foreach ($usuarios as $key => $usuario)
      {
        if ($usuario['cedula'] == $cedula)
        {
          // $this->log->logs("1. usuarios fecha_ret $fecha_ret < fecha_actual $fecha_actual", $usuario);
          if ($fecha_ret < $fecha_actual)
          {
            // $this->log->logs('usuarios ret manager', $usuarios[$key]);
            unset($usuarios[$key]);
            break;
          }
          $usuarios[$key]['nombre'] = $nombre;
          break;
        }
      }
    }

    if($id == "5")//director th
    {
      /* foreach ($usuarios as &$usuario)
        $usuario['zona'] = "9 - TALENTO HUMANO";
      unset($usuario); */

      $ccostosAdm = array();
      $zonas[] = "SELECCIONE";
      $ccostos = [["zona" => "", "ccostos" => "SELECCIONE"],];

      $sqlCcostosAdmin = "SELECT ccosto FROM th_ccostos_administrativo";
      $resCcostosAdmin = $this->cnn->query('0', $sqlCcostosAdmin);
      foreach($resCcostosAdmin as $res)
        $ccostosAdm[] = $res['ccosto'];

      $sqlZonasAdmin = "SELECT id, nombre FROM th_personal_administrativo_areas tpaa WHERE estado = '0' ORDER BY id";
      $resZonasAdmin = $this->cnn->query('0', $sqlZonasAdmin);
      foreach($resZonasAdmin as $res)
        $zonas[] = $res['id']." - ".$res['nombre'];
      $zonas[] = "12 - COMERCIAL ADMINISTRADORAS DE OFICINA";
      $zonas[] = "4139 - PROMOTORAS BETPLAY";

      $sql_aux_2 = "(37, 5)";
      if ($cedula == "1121966060" || $cedula == "1121893580" || $cedula == "1006820220")
        $sql_aux_2 = "(37)";

      /* $sqlUsuariosVacaciones = "SELECT tpa.cedula, concat(p.nombres, ' ', p.apellido1, ' ', p.apellido2) as nombre, tpa.periodo_compensatorio as comp, concat(tpa.id_per_adm_area, ' - ', tpaa2.nombre) as zona, tc.nombre AS nombre_cargo FROM th_cargos tc
      JOIN th_personal_administrativo_asignaciones tpaa ON tpaa.id_per_adm_cargo = tc.id AND tpaa.estado = '0'
      JOIN th_personal_administrativo tpa ON tpa.id = tpaa.id_per_adm_usuario_encargado AND tpa.estado = '0'
      JOIN th_personal_administrativo_areas tpaa2 ON tpaa2.id = tpa.id_per_adm_area AND tpaa2.estado = '0'
      LEFT JOIN personas p ON p.documento = tpa.cedula
      WHERE tc.id_cargo_permiso != '$id' AND tc.estado = '0' AND tc.id NOT IN $sql_aux_2"; */
      $sqlUsuariosVacaciones = "SELECT tpa.cedula, tpa.periodo_compensatorio as comp, concat(tpa.id_per_adm_area, ' - ', tpaa2.nombre) as zona, tc.nombre AS nombre_cargo FROM th_cargos tc
      JOIN th_personal_administrativo_asignaciones tpaa ON tpaa.id_per_adm_cargo = tc.id AND tpaa.estado = '0'
      JOIN th_personal_administrativo tpa ON tpa.id = tpaa.id_per_adm_usuario_encargado AND tpa.estado = '0'
      JOIN th_personal_administrativo_areas tpaa2 ON tpaa2.id = tpa.id_per_adm_area AND tpaa2.estado = '0'
      WHERE tc.id_cargo_permiso != '$id' AND tc.estado = '0' AND tc.id NOT IN $sql_aux_2";
      unset($resUsuariosVacaciones);
      $resUsuariosVacaciones = $this->cnn->query('0', $sqlUsuariosVacaciones);

      foreach($resUsuariosVacaciones as $res)
      {
        $count_cc++;
        $zona = $res['zona'];
        if ($res['nombre_cargo'] == "ADMINISTRADOR DE OFICINA" || $res['nombre_cargo'] == "ASISTENTE TESORERIA Y CARTERA" || $res['nombre_cargo'] == "AUXILIAR COMERCIAL MUNICIPIOS") $zona = "12 - COMERCIAL ADMINISTRADORAS DE OFICINA";
        else if ($res['nombre_cargo'] == "ASESORA DE VENTAS BETPLAY") $zona = "4139 - PROMOTORAS BETPLAY";

        array_push($usuarios, array("cedula" => $res['cedula'], "nombre" => "", "periodos" => 0, "rangoFecPtes" => "", "fecha_inicio" => "", "fecha_fin" => "", "reemplazo" => "", "zona" => $zona, "ccostos" => "", "perAceptados" => "0", "jornada" => "1", "fecPteInicio" => "", "fecPteFin" => ""));
        if ($count_cc < 1000) $cedulas[] = $res["cedula"];
        else $cedulas2[] = $res["cedula"];
      }

      $cedulas_separadas_por_comas = "'" . implode("','", $cedulas) . "'";
      $cedulas_separadas_por_comas2 = "'" . implode("','", $cedulas2) . "'";

      $auxSql = "";
      if (count($cedulas2) > 0) $auxSql = "AND cv.PRS_DOCUMENTO NOT IN ($cedulas_separadas_por_comas2)";
      $sqlVendedoras = "SELECT DISTINCT cv.PRS_DOCUMENTO as cedula, CONCAT(CONCAT(p.nombres,' '),p.apellido1) as nombre, CONCAT(CONCAT(cv.UBCNTRTRIO_CODIGO_COMPUESTO_DE,' - '),t.NOMBRE) AS zona,
      CONCAT(CONCAT(cv.UBCNEG_TRTRIO_CODIGO,' - '),t2.NOMBRE) AS ccostos, cv.UBCNEG_TRTRIO_CODIGO FROM CONTRATOSVENTA cv
      JOIN personas p ON p.DOCUMENTO = cv.PRS_DOCUMENTO
      JOIN TERRITORIOS t ON t.CODIGO = cv.UBCNTRTRIO_CODIGO_COMPUESTO_DE
      JOIN TERRITORIOS t2 ON t2.CODIGO = cv.UBCNEG_TRTRIO_CODIGO
      WHERE cv.FECHAFINAL IS NULL AND cv.PRS_DOCUMENTO NOT IN (1001,89090060825,890900608213,89090060823,89090060828,890900608210,890900608214,8909006082,890900608212,890900608211,89090060824,89090060827,89090060821,89090060826,89090060829,89090060822,9007379890)
      AND UBCNTRTRIO_CODIGO_COMPUESTO_DE IN (2126,2128,2130,3691,1080,1081,1082,2099,1083) AND cv.PRS_DOCUMENTO NOT IN ($cedulas_separadas_por_comas) AND cv.GRPVTAS_CODIGO IN (4,58) AND cv.FECHAFINAL IS NULL $auxSql";
      $resVendedoras=$this->cnn->query('2', $sqlVendedoras);

      foreach($resVendedoras as $res)
      {
        $count_cc++;

        $zonaVal = $res['ZONA'];
        $ccostosVal = $res['CCOSTOS'];
        if (in_array($res["UBCNEG_TRTRIO_CODIGO"], $ccostosAdm) && (($res["UBCNEG_TRTRIO_CODIGO"] == '1111' && $res["CEDULA"] == '1192775305') || $res["UBCNEG_TRTRIO_CODIGO"] != '1111'))
        {
          $zonaVal = "12 - COMERCIAL ADMINISTRADORAS DE OFICINA";
          $ccostosVal = "";
        }

        array_push($usuarios, array("cedula" => $res["CEDULA"], "nombre" => "", "periodos" => 0,"rangoFecPtes" => "", "fecha_inicio" => "", "fecha_fin" => "", "reemplazo" => "", "zona" => $zonaVal, "ccostos" => $ccostosVal, "perAceptados" => "0", "jornada" => "1", "fecPteInicio" => "", "fecPteFin" => ""));
        if ($count_cc < 1000) $cedulas[] = $res["CEDULA"];
        else $cedulas2[] = $res["CEDULA"];
        if (!in_array($res["ZONA"], $zonas)) $zonas[] = $res["ZONA"];

        $ccostosF = $res["CCOSTOS"];
        $registrado = array_filter($ccostos, function($registro) use ($ccostosF) {return $registro['ccostos'] === $ccostosF;});
        if (empty($registrado) && !in_array($res["UBCNEG_TRTRIO_CODIGO"], $ccostosAdm)) $ccostos[] = ["zona" => $res["ZONA"], "ccostos" => $ccostosF];
      }

      $cedulas_separadas_por_comas = "'" . implode("','", $cedulas) . "'";
      $cedulas_separadas_por_comas2 = "'" . implode("','", $cedulas2) . "'";
      $fecha_actual = date('Y-m-d');
      $auxSql = "";
      if (count($cedulas2) > 0) $auxSql = "OR VINCEDULA IN ($cedulas_separadas_por_comas2)";
      $sql_nombres_manager = "SELECT VINCEDULA, VINNOMBRE, to_char(VINFECRET, 'YYYY-MM-DD') AS VINFECRET FROM vinculado WHERE VINCEDULA IN ($cedulas_separadas_por_comas) $auxSql";
      $res_sql_nombres_manager = $this->cnn->query('5', $sql_nombres_manager);
      foreach($res_sql_nombres_manager as $res)
      {
        $cedula = $res['VINCEDULA'];
        $nombre = $res['VINNOMBRE'];
        $fecha_ret = $res['VINFECRET'];

        foreach ($usuarios as $key => $usuario)
        {
          if ($usuario['cedula'] == $cedula)
          {
            //$this->log->logs("2. usuarios fecha_ret $fecha_ret < fecha_actual $fecha_actual nombre $nombre", $usuario);
            if ($fecha_ret < $fecha_actual)
            {
              //$this->log->logs('usuarios ret manager', $usuarios[$key]);
              unset($usuarios[$key]);
              break;
            }
            $usuarios[$key]['nombre'] = $nombre;
            break;
          }
        }
      }
      $directorTH = true;
    }

    $cedulas_separadas_por_comas = "'" . implode("','", $cedulas) . "'";
    $cedulas_separadas_por_comas2 = "'" . implode("','", $cedulas2) . "'";

    $auxSql = "";
    if (count($cedulas2) > 0) $auxSql = "OR NEMCEDULA IN ($cedulas_separadas_por_comas2)";
    $sqlManagerVacaciones = "SELECT NEMCEDULA, TO_CHAR(NEMFECVACA, 'YYYY-MM-DD') AS NEMFECVACA, NEMJORNADA FROM nmempleado WHERE NEMCEDULA IN ($cedulas_separadas_por_comas) $auxSql";
    $resManagerVacaciones = $this->cnn->query('5', $sqlManagerVacaciones);

    $auxSql = "";
    if (count($cedulas2) > 0) $auxSql = "OR usuario IN ($cedulas_separadas_por_comas2)";
    $sqlCronogramaVacaciones = "SELECT usuario, fecha_inicio, fecha_fin, reemplazo, per_aceptados FROM th_cronograma_vacaciones WHERE usuario IN ($cedulas_separadas_por_comas) $auxSql";
    $resCronogramaVacaciones = $this->cnn->query('0', $sqlCronogramaVacaciones);

    foreach ($usuarios as $key => &$usuario)
    {
      $found = false;
      foreach($resManagerVacaciones as $resManager)
      {
        if($usuario["cedula"] == $resManager["NEMCEDULA"])
        {
          $usuario["periodos"] = $this->calcularAniosTranscurridos($resManager["NEMFECVACA"]);

          $fechaObjeto = new DateTime($resManager["NEMFECVACA"]);
          $fechaObjeto->modify("+1 day");
          $fechaIFecPtes = $fechaObjeto->format("Y-m-d");
          $fechaObjeto->modify("+".($usuario["periodos"]+1)." year");
          $usuario["fecPteInicio"] = $fechaIFecPtes;
          $usuario["fecPteFin"] = date("Y-m-d",strtotime($fechaObjeto->format("Y-m-d")." - 1 days"));
          $usuario["rangoFecPtes"] = $fechaIFecPtes . " AL " . date("Y-m-d",strtotime($fechaObjeto->format("Y-m-d")." - 1 days"));
          $usuario["jornada"] = $resManager["NEMJORNADA"];

          $found = true;
          break;
        }
      }
      if (!$found)
      {
        unset($usuarios[$key]);
        continue;
      }

      foreach($resCronogramaVacaciones as $resCronograma)
      {
        if($usuario["cedula"] == $resCronograma["usuario"])
        {
          $usuario["fecha_inicio"] = $resCronograma["fecha_inicio"];
          $usuario["fecha_fin"] = $resCronograma["fecha_fin"];
          $usuario["reemplazo"] = $resCronograma["reemplazo"];
          if(!empty($usuario["reemplazo"])) $cedulasReemplazos[] = $resCronograma['reemplazo'];
          $usuario["perAceptados"] = $resCronograma["per_aceptados"];
          break;
        }
      }
    }
    unset($usuario);

    if(!empty($cedulasReemplazos))
    {
      $cedulasReemplazosSeparadasPorComas = "'" . implode("','", $cedulasReemplazos) . "'";
      $sqlCedulasReemplazos = "SELECT VINCEDULA, VINNOMBRE  FROM VINCULADO v WHERE VINCEDULA IN ($cedulasReemplazosSeparadasPorComas)";
      $resCedulasReemplazos = $this->cnn->query('5', $sqlCedulasReemplazos);
      foreach ($usuarios as &$usuario)
      {
        foreach($resCedulasReemplazos as $resCedula)
        {
          if($usuario["reemplazo"] == $resCedula["VINCEDULA"])
          {
            $usuario["reemplazo"] .= " - " . $resCedula["VINNOMBRE"];
            break;
          }
        }
      }
      unset($usuario);
    }

    $festivos = array();
    $anio_act = date("Y");
    $anio_siguiente = date("Y", strtotime("+1 year"));
    $fechai_fes="01-01-".$anio_act;
    $fechaf_fes="31-12-".$anio_siguiente;
    $sql_fest = "SELECT date(fecha_festivo) as fest FROM tabla_festivos WHERE date(fecha_festivo) BETWEEN '$fechai_fes' AND '$fechaf_fes'";
    $res_festivos = $this->cnn->query('0', $sql_fest);
    foreach ($res_festivos as $row) array_push($festivos, $row['fest']);

    $this->log->logs("********************Fin consultaCronogramaVacaciones TH***********************");
    $respuesta['code'] = 1;
    $respuesta['usuarios'] = $usuarios;
    $respuesta['directorTH'] = $directorTH;
    $respuesta['zonas'] = $zonas;
    $respuesta['ccostos'] = $ccostos;
    $respuesta['festivos'] = $festivos;
    return $response->setContent(json_encode($respuesta));
  }

  public function registrarCronograma(Request $request)
  {
    $this->log->logs("********************Inicia registrarCronograma TH***********************");
    $response = new JsonResponse();
    $respuesta = array();
    $respuesta['code'] = 0;
    $usuarios = array();

    $content = $request->getContent();
    $jsonContent = json_decode($content, true);
    $arrCronogramas=(!empty($jsonContent['arrCronograma'])) ? $jsonContent['arrCronograma'] : null;
    foreach ($arrCronogramas as $arrCronograma) $usuarios[] = $arrCronograma[0];

    $usuariosSeparadosXComas = "'" . implode("','", $usuarios) . "'";
    $sqlValCronograma = "SELECT usuario FROM th_cronograma_vacaciones WHERE usuario IN ($usuariosSeparadosXComas)";
    $resValCronograma = $this->cnn->query('0', $sqlValCronograma);

    $sqlManagerVacaciones = "SELECT NEMCEDULA, TO_CHAR(NEMFECVACA, 'YYYY-MM-DD') AS NEMFECVACA, NEMJORNADA FROM nmempleado WHERE NEMCEDULA IN ($usuariosSeparadosXComas)";
    $resManagerVacaciones = $this->cnn->query('5', $sqlManagerVacaciones);

    foreach ($arrCronogramas as &$arrCronograma)
    {
      $sqlAux = "fecha_inicio = '$arrCronograma[1]', fecha_fin = '$arrCronograma[2]'";
      $sqlAux2 = "'$arrCronograma[1]','$arrCronograma[2]'";
      if(empty($arrCronograma[1]) || empty($arrCronograma[2]))
      {
        $arrCronograma[1] = "null";
        $arrCronograma[2] = "null";
        $arrCronograma[3] = "";
        $sqlAux = "fecha_inicio = $arrCronograma[1], fecha_fin = $arrCronograma[2]";
        $sqlAux2 = "$arrCronograma[1],$arrCronograma[2]";
      }

      $encontrado = false;
      $fechaIniUltPeriodo = null;
      $jornada = 1;
      foreach($resManagerVacaciones as $resManager)
      {
        if($arrCronograma[0] == $resManager["NEMCEDULA"])
        {
          $fechaIniUltPeriodo = $resManager["NEMFECVACA"];
          $jornada = $resManager["NEMJORNADA"];
          break;
        }
      }
      foreach ($resValCronograma as $res)
      {
        if($res["usuario"] == $arrCronograma[0])
        {
          $sql = "UPDATE th_cronograma_vacaciones SET $sqlAux, reemplazo = '$arrCronograma[3]', per_aceptados = '$arrCronograma[4]', jornada = '$jornada',
            fecha_ini_ult_periodo = '$fechaIniUltPeriodo', usuario_mod = '$this->nickname', fecha_mod = NOW() WHERE usuario = '$arrCronograma[0]' RETURNING id;";
          $encontrado = true;
          break;
        }
      }
      if(!$encontrado)
      {
        $sql = "INSERT INTO th_cronograma_vacaciones (usuario, fecha_inicio, fecha_fin, reemplazo, fecha_ini_ult_periodo, per_aceptados, jornada, usuario_mod) VALUES ('$arrCronograma[0]',$sqlAux2,'$arrCronograma[3]','$fechaIniUltPeriodo','$arrCronograma[4]','$jornada','$this->nickname') RETURNING id;";
      }
      $resSql = $this->cnn->query('0', $sql);
      if(count($resSql)<=0)
      {
        $respuesta["respuesta"] = "No se actualizo el cronograma, intente nuevamente.";
        return $response->setContent(json_encode($respuesta));
      }
    }

    $this->log->logs("********************Fin registrarCronograma TH***********************");
    $respuesta['code'] = 1;
    $respuesta['respuesta'] = "Cronograma actualizado.";
    return $response->setContent(json_encode($respuesta));
  }

  public function consultaCargueCronogramaVacaciones()
  {
    $this->log->logs("********************Inicia consultaCargueCronogramaVacaciones TH***********************");
    $response = new JsonResponse();
    $respuesta = array();
    $zonas = array();
    $respuesta['code'] = 0;

    $cedula = substr($this->nickname, 2);
    $sqlValProgVacaciones = "SELECT tc.id, tc.nombre FROM th_personal_administrativo tpa
    JOIN th_personal_administrativo_asignaciones tpaa ON tpaa.id_per_adm_usuario_encargado = tpa.id AND tpaa.estado = '0'
    JOIN th_cargos tc ON tc.id = tpaa.id_per_adm_cargo AND tc.estado = '0' AND tc.id IN
    (SELECT id_cargo_permiso FROM th_cargos tc WHERE id_cargo_permiso NOT IN (34) AND estado = 0 GROUP BY id_cargo_permiso)
    WHERE tpa.cedula = '$cedula'";
    $resValProgVacaciones = $this->cnn->query('0', $sqlValProgVacaciones);
    if(count($resValProgVacaciones)<=0)
    {
      $respuesta["datos"] = "Usted no puede programar vacaciones, comuniquese con TH";
      return $response->setContent(json_encode($respuesta));
    }
    $id = $resValProgVacaciones[0]["id"];
    $directorTH = false;

    if($id == "5")//director th
    {
      $zonas[] = "0000 - ADMINISTRATIVOS";
      $sqlZonas = "SELECT CONCAT(CONCAT(CODIGO,' - '),NOMBRE) as zona FROM TERRITORIOS t  WHERE CODIGO IN (2126,2128,2130,3691,1080,1081,1082,2099,1083)";
      $resZonas=$this->cnn->query('2', $sqlZonas);
      foreach($resZonas as $res) $zonas[] = $res['ZONA'];
      $directorTH = true;
    }

    $this->log->logs("********************Fin consultaCargueCronogramaVacaciones TH***********************");
    $respuesta['code'] = 1;
    $respuesta['directorTH'] = $directorTH;
    $respuesta['zonas'] = $zonas;
    return $response->setContent(json_encode($respuesta));
  }

  public function cargarCronograma(Request $request)
  {
    $this->log->logs("********************Inicia cargarCronograma TH***********************");
    $response = new JsonResponse();
    $respuesta = array();
    $cedulas = array();
    $cedulasCronograma = array();
    $respuesta['code'] = 0;

    if ($request->files->count() <= 0)
    {
      $respuesta["respuesta"] = "No se recibio el archivo, intente nuevamente.";
      return $response->setContent(json_encode($respuesta));
    }
    $archivo = $request->files->get('fileInput');
    $reader = IOFactory::createReader('Xlsx');
    $spreadsheet = $reader->load($archivo->getPathname());
    $hoja = $spreadsheet->getActiveSheet();
    $datos = [];
    $guardarDatos = false;
    $finalizado = false;
    foreach ($hoja->getRowIterator() as $fila)
    {
      $celdas = $fila->getCellIterator();
      $filaDatos = [];
      $ciclos = 0;
      foreach ($celdas as $key => $celda)
      {
        if($ciclos > 6) break;
        if(trim($celda->getValue()) == "PROCESO" && $key == "A")
        {
          $guardarDatos = true;
          break;
        }
        else if(trim($celda->getValue()) == "OBSERVACIONES:" && $key == "A")
        {
          $finalizado = true;
          break;
        }
        $ciclos++;
        $filaDatos[] = $celda->getValue();
      }
      if($finalizado) break;
      if($guardarDatos && !empty(array_filter($filaDatos, 'strlen'))) $datos[] = $filaDatos;
    }
    foreach ($datos as $dato) $cedulasCronograma[] = $dato[1];
    $this->log->logs("Datos del Excel",$datos);

    $cedula = substr($this->nickname, 2);
    $sqlValProgVacaciones = "SELECT tc.id, tc.nombre, tc.id_per_adm_area FROM th_personal_administrativo tpa
    JOIN th_personal_administrativo_asignaciones tpaa ON tpaa.id_per_adm_usuario_encargado = tpa.id AND tpaa.estado = '0'
    JOIN th_cargos tc ON tc.id = tpaa.id_per_adm_cargo AND tc.estado = '0' AND tc.id IN
    (SELECT id_cargo_permiso FROM th_cargos tc WHERE id_cargo_permiso NOT IN (34) AND estado = 0 GROUP BY id_cargo_permiso)
    WHERE tpa.cedula = '$cedula'";
    $resValProgVacaciones = $this->cnn->query('0', $sqlValProgVacaciones);
    if(count($resValProgVacaciones)<=0)
    {
      $respuesta["datos"] = "Usted no puede programar vacaciones, comuniquese con TH";
      return $response->setContent(json_encode($respuesta));
    }
    $id = $resValProgVacaciones[0]["id"];
    $id_area = $resValProgVacaciones[0]["id_per_adm_area"];
    $sql_aux = "(tc.id_cargo_permiso = '$id' OR tc.id = '$id')";
    if ($id == 79)
      $sql_aux = "tc.id_per_adm_area = $id_area";

    $sqlUsuariosVacaciones = "SELECT tpa.cedula FROM th_cargos tc
    JOIN th_personal_administrativo_asignaciones tpaa ON tpaa.id_per_adm_cargo = tc.id AND tpaa.estado = '0'
    JOIN th_personal_administrativo tpa ON tpa.id = tpaa.id_per_adm_usuario_encargado AND tpa.estado = '0'
    WHERE $sql_aux";
    $resUsuariosVacaciones = $this->cnn->query('0', $sqlUsuariosVacaciones);
    if(count($resUsuariosVacaciones)<=0)
    {
      $respuesta["datos"] = "No tiene personas asignadas para programar vacaciones, comuniquese con TH";
      return $response->setContent(json_encode($respuesta));
    }
    foreach($resUsuariosVacaciones as $res) $cedulas[] = $res['cedula'];

    /* if($id == "5")//director th
    {
      $sqlVendedoras = "SELECT DISTINCT cv.PRS_DOCUMENTO as cedula FROM CONTRATOSVENTA cv
        JOIN personas p ON p.DOCUMENTO = cv.PRS_DOCUMENTO
        JOIN TERRITORIOS t ON t.CODIGO = cv.UBCNTRTRIO_CODIGO_COMPUESTO_DE
        WHERE cv.FECHAFINAL IS NULL AND cv.PRS_DOCUMENTO NOT IN (1001,89090060825,890900608213,89090060823,89090060828,890900608210,890900608214,8909006082,890900608212,890900608211,89090060824,89090060827,89090060821,89090060826,89090060829,89090060822,9007379890)
        AND UBCNTRTRIO_CODIGO_COMPUESTO_DE IN (2126,2128,2130,3691,1080,1081,1082,2099,1083)";
      $resVendedoras=$this->cnn->query('2', $sqlVendedoras);
      foreach($resVendedoras as $res) $cedulas[] = $res['CEDULA'];
    } */

    $usuariosSeparadosXComas = "'" . implode("','", $cedulasCronograma) . "'";
    $sqlValCronograma = "SELECT usuario FROM th_cronograma_vacaciones WHERE usuario IN ($usuariosSeparadosXComas)";
    $resValCronograma = $this->cnn->query('0', $sqlValCronograma);

    $sqlManagerVacaciones = "SELECT NEMCEDULA, TO_CHAR(NEMFECVACA, 'YYYY-MM-DD') AS NEMFECVACA, NEMJORNADA FROM nmempleado WHERE NEMCEDULA IN ($usuariosSeparadosXComas)";
    $resManagerVacaciones = $this->cnn->query('5', $sqlManagerVacaciones);

    //aqui se debe validar los usuarios autorizados para registrarles vacaciones
    $errores = "";
    foreach($datos as $dato)
    {
      if ($id == "5" || in_array($dato[1], $cedulas))
      {
        $encontrado = false;
        $arrFechas = explode(" ",$dato[5]);
        if (empty($arrFechas[0]) || empty($arrFechas[2]))
          continue;

        $fechaIniUltPeriodo = null;
        $jornada = 1;
        foreach($resManagerVacaciones as $resManager)
        {
          if($dato[1] == $resManager["NEMCEDULA"])
          {
            $fechaIniUltPeriodo = $resManager["NEMFECVACA"];
            $jornada = $resManager["NEMJORNADA"];
            break;
          }
        }
        foreach ($resValCronograma as $res)
        {
          if($res["usuario"] == $dato[1])
          {
            $sql = "UPDATE th_cronograma_vacaciones SET fecha_inicio = '$arrFechas[0]', fecha_fin = '$arrFechas[2]', per_aceptados = 0, reemplazo = '', fecha_ini_ult_periodo = '$fechaIniUltPeriodo', jornada = '$jornada', usuario_mod = '$this->nickname', fecha_mod = NOW() WHERE usuario = '$dato[1]' RETURNING id;";
            $encontrado = true;
            break;
          }
        }
        if(!$encontrado)
        {
          $sql = "INSERT INTO th_cronograma_vacaciones (usuario, fecha_inicio, fecha_fin, reemplazo, fecha_ini_ult_periodo, per_aceptados, jornada, usuario_mod) VALUES ('$dato[1]','$arrFechas[0]','$arrFechas[2]','','$fechaIniUltPeriodo',0,'$jornada','$this->nickname') RETURNING id;";
        }
        $resSql = $this->cnn->query('0', $sql);
        if(count($resSql)<=0) $errores .= "<br><p style='font-size: x-large;'>No se pudo registrar la informacion del Usuario $dato[1] - $dato[2]";
      }
      else $errores .= "<br><p style='font-size: x-large;'>Su cargo no tiene permiso para registrar la informacion del Usuario $dato[1] - $dato[2]";
    }

    $this->log->logs("********************Fin cargarCronograma TH***********************");
    $respuesta['code'] = 1;
    $respuesta['respuesta'] = "Cronograma cargado.";
    $respuesta['errores'] = $errores;
    return $response->setContent(json_encode($respuesta));
  }

  public function notificarGestionVacaciones()
  {
    $this->log->logs("********************Inicia notificarGestionVacaciones TH***********************");
    $cedulas = array();
    $vacacionesPtes = array();
    $jefesGamble = array();
    $jefes = array();

    /* $sql = "SELECT tcv.usuario AS cedula, concat(p.nombres, ' ', p.apellido1, ' ', p.apellido2) as nombre, tcv.fecha_inicio, tcv.fecha_fin
    FROM th_cronograma_vacaciones tcv LEFT JOIN personas p ON p.documento::text = tcv.usuario
    WHERE tcv.fecha_inicio = CURRENT_DATE + INTERVAL '5 days'"; */
    $sql = "SELECT tcv.usuario AS cedula, v.vinnombre as nombre, tcv.fecha_inicio, tcv.fecha_fin
    FROM th_cronograma_vacaciones tcv JOIN manager_50.vinculado v ON v.vincedula = tcv.usuario
    WHERE tcv.fecha_inicio = CURRENT_DATE + INTERVAL '5 days'";
    $resVacacionesPtes = $this->cnn->query('0', $sql);
    if(count($resVacacionesPtes) <= 0){return array("status" =>1,"message"=>"No hay vacaciones pendientes.");}
    foreach ($resVacacionesPtes as $res)
    {
      $vacacionesPtes[] = array("cedula" => $res["cedula"], "nombre" => $res["nombre"], "fecha_inicio" => $res["fecha_inicio"], "fecha_fin" => $res["fecha_fin"], "ced_jefe" => "", "correo_jefe" => "", "correo" => "");
      $cedulas[] = $res["cedula"];
    }

    $strCedulas = "'" . implode("','", $cedulas) . "'";
    $gamble = "SELECT usu.PRS_DOCUMENTO, un.trtrio_codigo_compuesto_de,t.NOMBRE, SUBSTR(u.LOGINUSR, 3) AS supervisor
    FROM usuarios u, rolesusuarios r, contratopersonas cp, ubicacionnegocios un, territorios t, (SELECT DISTINCT cv.PRS_DOCUMENTO, cv.UBCNTRTRIO_CODIGO_COMPUESTO_DE FROM CONTRATOSVENTA cv
    JOIN personas p ON p.DOCUMENTO = cv.PRS_DOCUMENTO WHERE cv.FECHAFINAL IS NULL AND cv.PRS_DOCUMENTO IN ($strCedulas)) usu
    WHERE usu.UBCNTRTRIO_CODIGO_COMPUESTO_DE = un.trtrio_codigo_compuesto_de and
    u.loginusr=r.loginusr and u.estado='A' and u.loginusr=cp.login and cp.fechafinal is null
    AND cp.ubcntrtrio_codigo_compuesto_de=un.trtrio_codigo_compuesto_de and cp.ubcneg_trtrio_codigo=un.trtrio_codigo and t.codigo=un.trtrio_codigo_compuesto_de
    AND r.role='ROL_SUPERVISOR' and un.trtrio_codigo not in('2948','1078') and cp.login not in ('CP40342853')";
    $resUsuarioGamble=$this->cnn->query('2', $gamble);

    foreach ($vacacionesPtes as &$res)
    {
      $cedula = $res["cedula"];
      $foundRecords = array_values(array_filter($resUsuarioGamble, function($record) use ($cedula) {return $record["PRS_DOCUMENTO"] == $cedula;}));
      if (!empty($foundRecords))
      {
        $jefesGamble[] = $foundRecords[0]["SUPERVISOR"];
        $res["ced_jefe"] = $foundRecords[0]["SUPERVISOR"];
      }
      else
      {
        $jefes[] = $cedula;
        $res["ced_jefe"] = $cedula;
      }
    }
    unset($res);

    if(count($jefesGamble)>0)
    {
      $strJefesGamble = "'" . implode("','", $jefesGamble) . "'";
      $sqlCorreoJefe = "SELECT cedula, correo FROM th_personal_administrativo tpa WHERE cedula IN ($strJefesGamble)";
      $resCorreoJefe=$this->cnn->query('0', $sqlCorreoJefe);
      foreach ($vacacionesPtes as &$res)
      {
        $cedJefe = $res["ced_jefe"];
        $foundRecords = array_values(array_filter($resCorreoJefe, function($record) use ($cedJefe) {return $record["cedula"] == $cedJefe;}));
        if (!empty($foundRecords)) $res["correo_jefe"] = $foundRecords[0]["correo"];
      }
      unset($res);
    }
    if(count($jefes)>0)
    {
      $strJefes = "'" . implode("','", $jefes) . "'";
      $sqlCorreoJefe = "SELECT tpa.cedula, tc.id_cargo_permiso, tc2.nombre, tpa2.correo FROM th_personal_administrativo tpa
      JOIN th_personal_administrativo_asignaciones tpaa ON tpaa.id_per_adm_usuario_encargado = tpa.id AND tpaa.estado = '0'
      JOIN th_cargos tc ON tc.id = tpaa.id_per_adm_cargo AND tc.estado = '0'
      JOIN th_cargos tc2 ON tc2.id = tc.id_cargo_permiso
      JOIN th_personal_administrativo_asignaciones tpaa2 ON tpaa2.id_per_adm_cargo = tc2.id AND tpaa2.estado = '0'
      JOIN th_personal_administrativo tpa2 ON tpa2.id = tpaa2.id_per_adm_usuario_encargado and tpa2.estado='0'
      WHERE tpa.cedula IN ($strJefes)";
      $resCorreoJefe=$this->cnn->query('0', $sqlCorreoJefe);
      foreach ($vacacionesPtes as &$res)
      {
        $cedJefe = $res["ced_jefe"];
        $foundRecords = array_values(array_filter($resCorreoJefe, function($record) use ($cedJefe) {return $record["cedula"] == $cedJefe;}));
        if (!empty($foundRecords)) $res["correo_jefe"] = $foundRecords[0]["correo"];
      }
      unset($res);
    }

    $sqlManager = "SELECT v.VINEMAIL, v.VINCEDULA, n.NEMJORNADA FROM VINCULADO v JOIN NMEMPLEADO n ON n.NEMCEDULA = v.VINCEDULA WHERE v.VINCEDULA IN ($strCedulas) AND VINFECRET >= TRUNC(SYSDATE)";
    $resultManager = $this->cnn->query('5', $sqlManager);
    foreach ($vacacionesPtes as &$res)
    {
      $cedula = $res["cedula"];
      $foundRecords = array_values(array_filter($resultManager, function($record) use ($cedula) {return trim($record["VINCEDULA"]) == $cedula;}));
      if (!empty($foundRecords))
      {
        $res["correo"] = $foundRecords[0]["VINEMAIL"];
        $jornada = $foundRecords[0]["NEMJORNADA"];
        $sqlUpdt = "UPDATE th_cronograma_vacaciones SET jornada = '$jornada' WHERE usuario = '$cedula'";
        $this->cnn->query('0', $sqlUpdt);
      }
    }
    unset($res);

    $cedulasNotificadas = array();
    $destinatarios[0]="acespedes@consuerte.com.co";
    $destinatarios[1]="ajquebradab@consuerte.com.co";
    $destinatarios[2]="asisnomina@consuerte.com.co";
    foreach ($vacacionesPtes as $res)
    {
      $destinatarios[3]=$res["correo"];
      if (!empty($res["correo_jefe"])) $destinatarios[4]=$res["correo_jefe"];
      if (empty($destinatarios[3])) continue;

      $cedula = $res["cedula"];
      $cedulasNotificadas[] = $res["cedula"];
      $nombre = $res["nombre"];
      $fechaInicio = $res["fecha_inicio"];

      $asunto = "Notificación de Vacaciones Próximas a Vencer";
      $informacion = "Señor(a) $nombre identificado con CC $cedula.".
      "<br>Le informamos que sus vacaciones están próximas a vencer. Tiene 5 días para gestionar sus vacaciones a través de SEM Mobile.".
      "<br>Su fecha de inicio programada de vacaciones es desde el $fechaInicio.".
      "<br><br>Si necesita más información o realizar una modificación de sus vacaciones, por favor comuníquese con el area de Talento Humano.";
      $this->enviar_correo($this->email,$asunto,$informacion,$destinatarios,"NOTIFICACIÓN DE VACACIONES PRÓXIMAS A VENCER");
    }

    $cedulasBloqueo = array();
    $cedulasDesbloqueo = array();
    $sql2 = "SELECT tcv.usuario AS cedula, tcv.fecha_inicio, (tcv.fecha_fin + INTERVAL '1 days')::date AS fecha_fin FROM th_cronograma_vacaciones tcv WHERE (tcv.fecha_inicio = CURRENT_DATE OR (tcv.fecha_fin + INTERVAL '1 days') = CURRENT_DATE)";
    $res2 = $this->cnn->query('0', $sql2);
    if(count($res2) <= 0){return array("status" =>1,"message"=>"No hay Usuarios para bloquear o desbloquear por vacaciones.");}
    foreach ($res2 as $res)
    {
      $vacacionesPtes[] = array("cedula" => $res["cedula"], "nombre" => $res["nombre"], "fecha_inicio" => $res["fecha_inicio"], "fecha_fin" => $res["fecha_fin"], "ced_jefe" => "", "correo_jefe" => "", "correo" => "");
      if (date("Y-m-d") == $res["fecha_inicio"])
      {
        $cedulasBloqueo[] = $res["cedula"];
      }
      else if (date("Y-m-d") == $res["fecha_fin"])
      {
        $cedulasDesbloqueo[] = $res["cedula"];
      }
    }
    $usuariosDesbloquear = "'" . implode("','", $cedulasDesbloqueo) . "'";
    $this->bloqueoUsuarios($cedulasBloqueo);
    $this->desbloqueoUsuarios($usuariosDesbloquear);

    $this->log->logs("********************Fin notificarGestionVacaciones TH***********************");
    return array("status" =>0,"message"=>"Proceso Exitoso","usuariosNotificados"=>$cedulasNotificadas,"usuariosBloqueados"=>$cedulasBloqueo,"usuariosDesbloqueados"=>$cedulasDesbloqueo);
  }

  public function inactivarUsuariosGrafoTH()
  {
    $this->log->logs("********************Inicia inactivarUsuariosGrafoTH***********************");
    $cedulas = array();

    $sql = "SELECT STRING_AGG(cedula::text, ',') AS cedulas FROM th_personal_administrativo WHERE estado = 0";
    $resUsuarios = $this->cnn->query('0', $sql);
    if(count($resUsuarios) <= 0)
      return array("status" =>1,"message"=>"No se encontraron usuarios activos en el grafo TH.");

    $strCedulas = "'".str_replace(",", "','", $resUsuarios[0]['cedulas'])."'";
    $sqlRetiradosManager = "SELECT trim(VINCEDULA) AS VINCEDULA FROM VINCULADO v WHERE VINCEDULA IN ($strCedulas) AND VINFECRET < TRUNC(SYSDATE)";
    $resRetiradosManager = $this->cnn->query('5', $sqlRetiradosManager);
    if(count($resRetiradosManager) <= 0)
      return array("status" =>1,"message"=>"No se encontraron usuarios retirados en manager.");

    foreach ($resRetiradosManager as &$res)
      $cedulas[] = $res["VINCEDULA"];
    $strCedulasManager = "'" . implode("','", $cedulas) . "'";

    $sqlUpdt = "UPDATE th_personal_administrativo set estado = '1', fecha_mod = now(), usuario_mod = 'CP1006820220' WHERE cedula IN ($strCedulasManager)";
    $this->cnn->query('0', $sqlUpdt);
    $this->log->logs("********************Fin inactivarUsuariosGrafoTH***********************");
    return array("status" =>0,"message"=>"Proceso Exitoso","usuarios inactivados"=>$cedulas);
  }

  public function generarExcelFormatoVacaciones(Request $request)
  {
    $this->log->logs("********************Inicia generarExcelFormatoVacaciones TH***********************");
    $usuarios = array();
    $content = $request->getContent();
    $jsonContent = json_decode($content, true);
    $arrCronogramas=(!empty($jsonContent['arrCronograma'])) ? $jsonContent['arrCronograma'] : null;

    usort($arrCronogramas, function ($a, $b)
    {
      if ($a[1] === null && $b[1] !== null)
        return 1; // $a va al final
      elseif ($a[1] !== null && $b[1] === null)
        return -1; // $b va al final

      // Si ambos elementos tienen fecha, comparamos las fechas
      $fechaA = strtotime($a[1]); // Convertir la fecha a timestamp
      $fechaB = strtotime($b[1]); // Convertir la fecha a timestamp

      if ($fechaA === false) $fechaA = PHP_INT_MAX; // Si la fecha es inválida, mover al final
      if ($fechaB === false) $fechaB = PHP_INT_MAX; // Lo mismo para $b

      return $fechaA - $fechaB; // Orden ascendente por fecha
    });

    foreach ($arrCronogramas as $arrCronograma) $usuarios[] = $arrCronograma[0];
    // $this->log->logs("",$arrCronogramas);

    $usuariosSeparadosXComas = "'" . implode("','", $usuarios) . "'";
    /* $sqlAreaUsuarios = "SELECT tpa.cedula,tpaa.nombre AS nombre_area, concat(p.nombres, ' ', p.apellido1, ' ', p.apellido2) AS nombre_persona FROM th_personal_administrativo tpa
    JOIN th_personal_administrativo_areas tpaa ON tpaa.id = tpa.id_per_adm_area
    JOIN personas p ON p.documento = tpa.cedula
    WHERE tpa.cedula IN ($usuariosSeparadosXComas) AND tpa.estado = '0' AND tpaa.estado = '0'"; */
    $sqlAreaUsuarios = "SELECT tpa.cedula,tpaa.nombre AS nombre_area, concat(p.nombres, ' ', p.apellido1, ' ', p.apellido2) AS nombre_persona FROM th_personal_administrativo tpa
    JOIN th_personal_administrativo_areas tpaa ON tpaa.id = tpa.id_per_adm_area
    JOIN personas p ON p.documento = tpa.cedula
    WHERE tpa.cedula IN ($usuariosSeparadosXComas) AND tpa.estado = '0' AND tpaa.estado = '0'
    UNION ALL
    SELECT p2.documento, CONCAT(CONCAT(cv.UBCNEG_TRTRIO_CODIGO,' - '),t2.NOMBRE) AS ccostos, concat(p2.nombres, ' ', p2.apellido1, ' ', p2.apellido2) FROM CONTRATOSVENTA cv
    JOIN personas p2 ON p2.DOCUMENTO = cv.PRS_DOCUMENTO
    JOIN TERRITORIOS t ON t.CODIGO = cv.UBCNTRTRIO_CODIGO_COMPUESTO_DE
    JOIN TERRITORIOS t2 ON t2.CODIGO = cv.UBCNEG_TRTRIO_CODIGO
    WHERE p2.documento IN ($usuariosSeparadosXComas) AND cv.FECHAFINAL IS NULL";
    $resAreasUsuarios = $this->cnn->query('0', $sqlAreaUsuarios);

    $spreadsheet = new Spreadsheet();
    $spreadsheet->getProperties()->setCreator('CONSUERTE')->setTitle('Reporte programacion de vacaciones');
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->getColumnDimension('A')->setWidth(20); // Ajusta según sea necesario
    $sheet->getColumnDimension('B')->setWidth(25);
    $sheet->getColumnDimension('C')->setWidth(40);
    $sheet->getColumnDimension('D')->setWidth(30);
    $sheet->getColumnDimension('E')->setWidth(26);
    $sheet->getColumnDimension('F')->setWidth(40);
    $sheet->getColumnDimension('G')->setWidth(20);
    $sheet->getColumnDimension('H')->setWidth(20);
    $sheet->getRowDimension(1)->setRowHeight(19);
    $sheet->getRowDimension(2)->setRowHeight(19);
    $sheet->getRowDimension(3)->setRowHeight(19);
    $sheet->getRowDimension(5)->setRowHeight(8);
    $sheet->getRowDimension(6)->setRowHeight(50);

    // Insertar imagen
    $drawing = new Drawing();
    $drawing->setName('Logo');
    $drawing->setDescription('Logo');
    $imagePath = $this->ruta."/public/uploads/talento_humano/logo.jpg";
    $path = realpath($imagePath);
    if ($path === false)
    {
      $this->log->logs("Image not found");
      throw new \Exception('Image not found');
    }
    $drawing->setPath($path);
    $drawing->setCoordinates('A1'); // Posición de la imagen
    $drawing->setHeight(73); // Ajustar el tamaño de la imagen
    $drawing->setWorksheet($spreadsheet->getActiveSheet());
    $sheet->mergeCells('A1:B3');
    $sheet->getStyle('A1:B3')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_MEDIUM);

    // Unir celdas para el título
    $sheet->mergeCells('C1:E2');
    $sheet->setCellValue('C1', 'FORMATO');
    $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(18);
    $sheet->getStyle('C1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('C1')->getAlignment()->setVertical(Alignment::HORIZONTAL_CENTER);

    // Segunda línea de título
    $sheet->mergeCells('C3:E3');
    $sheet->setCellValue('C3', mb_convert_encoding('PROGRAMACIÓN DE VACACIONES', 'UTF-8', 'ISO-8859-1'));
    $sheet->getStyle('C3')->getFont()->setBold(false)->setSize(14);
    $sheet->getStyle('C3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Añadir las celdas de código, versión y fecha
    $sheet->setCellValue('F1', mb_convert_encoding('CÓDIGO:', 'UTF-8', 'ISO-8859-1'));
    $sheet->setCellValue('F2', mb_convert_encoding('VERSIÓN:', 'UTF-8', 'ISO-8859-1'));
    $sheet->setCellValue('F3', 'FECHA:');

    $sheet->mergeCells('G1:H1');
    $sheet->setCellValue('G1', 'FO-TH-003');
    $sheet->mergeCells('G2:H2');
    $sheet->setCellValue('G2', '2.1');
    $sheet->mergeCells('G3:H3');
    $sheet->setCellValue('G3', '12/01/2021');
    $styleArray = [
      'alignment' => [
          'horizontal' => Alignment::HORIZONTAL_LEFT,
      ],
      'borders' => [
          'allBorders' => [
              'borderStyle' => Border::BORDER_MEDIUM,
          ],
      ],
    ];
    $sheet->getStyle('G1:H3')->applyFromArray($styleArray);

    $styleArray = [
      'font' => [
          'bold' => true,
      ],
      'borders' => [
          'allBorders' => [
              'borderStyle' => Border::BORDER_MEDIUM,
          ],
      ],
      'alignment' => [
          'horizontal' => Alignment::HORIZONTAL_RIGHT,
          'vertical' => Alignment::VERTICAL_CENTER,
      ],
      'fill' => [
          'fillType' => Fill::FILL_SOLID,
          'startColor' => [
              'argb' => 'FFCCCCCC', // Color de fondo gris
          ],
      ],
    ];
    $sheet->getStyle('F1:F3')->applyFromArray($styleArray);

    $sheet->mergeCells('A4:H4');
    $sheet->setCellValue('A4', 'PROCESO: TALENTO HUMANO');
    $styleArray = [
      'font' => [
          'bold' => true,
      ],
      'alignment' => [
          'horizontal' => Alignment::HORIZONTAL_LEFT,
      ],
      'fill' => [
          'fillType' => Fill::FILL_SOLID,
          'startColor' => [
              'argb' => 'FFCCCCCC',
          ],
      ],
      'borders' => [
          'allBorders' => [
              'borderStyle' => Border::BORDER_MEDIUM,
          ],
      ],
    ];
    $sheet->getStyle('A4:H4')->applyFromArray($styleArray);

    $sheet->setCellValue('A6', 'PROCESO');
    $sheet->setCellValue('B6', 'CEDULA');
    $sheet->setCellValue('C6', 'NOMBRE');
    $sheet->setCellValue('D6', 'RANGO FECHAS PENDIENTES');
    $sheet->setCellValue('E6', mb_convert_encoding('NÚMERO PERIODOS', 'UTF-8', 'ISO-8859-1'));
    $sheet->setCellValue('F6', mb_convert_encoding('PROGRAMACIÓN FECHA DE VACACIONES', 'UTF-8', 'ISO-8859-1'));
    $sheet->mergeCells('G6:H6');
    $sheet->setCellValue('G6', 'REEMPLAZA');
    $styleArray = [
      'font' => [
          'bold' => true,
      ],
      'borders' => [
          'allBorders' => [
              'borderStyle' => Border::BORDER_MEDIUM,
          ],
      ],
      'alignment' => [
          'horizontal' => Alignment::HORIZONTAL_CENTER,
          'vertical' => Alignment::VERTICAL_CENTER,
      ],
      'fill' => [
          'fillType' => Fill::FILL_SOLID,
          'startColor' => [
              'argb' => 'FFCCCCCC', // Color de fondo gris
          ],
      ],
    ];
    $sheet->getStyle('A6:H6')->applyFromArray($styleArray);

    $fila = 7;
    foreach ($arrCronogramas as $arrCronograma)
    {
      $area = "DESCONOCIDA";
      $nombre = "DESCONOCIDO";
      foreach ($resAreasUsuarios as $res)
      {
        if($res["cedula"] == $arrCronograma[0])
        {
          $area = $res["nombre_area"];
          $nombre = $res["nombre_persona"];
          break;
        }
      }

      $sheet->getRowDimension($fila)->setRowHeight(20);
      $sheet->setCellValue('A' . $fila, $area);
      $sheet->setCellValue('B' . $fila, $arrCronograma[0]);
      $sheet->setCellValue('C' . $fila, $nombre);
      $sheet->setCellValue('D' . $fila, $arrCronograma[5]);
      $sheet->setCellValue('E' . $fila, $arrCronograma[4]);
      if (!empty($arrCronograma[1]))
        $sheet->setCellValue('F' . $fila, $arrCronograma[1] . " A " . $arrCronograma[2]);

      $sheet->mergeCells("G$fila:H$fila");
      $sheet->setCellValue('G' . $fila, $arrCronograma[3]);
      $styleArray = [
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_LEFT,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
      ];
      $sheet->getStyle("A$fila:H$fila")->applyFromArray($styleArray);
      $fila++;
    }
    $sheet->getRowDimension($fila)->setRowHeight(8);
    $fila++;
    $sheet->getRowDimension($fila)->setRowHeight(80);

    $sheet->mergeCells("A$fila:H$fila");
    $sheet->setCellValue("A$fila", 'OBSERVACIONES:');
    $styleArray = [
      'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_LEFT,
            'vertical' => Alignment::VERTICAL_TOP,
        ],
      'font' => [
          'bold' => true,
      ],
      'borders' => [
          'allBorders' => [
              'borderStyle' => Border::BORDER_MEDIUM,
          ],
      ],
    ];
    $sheet->getStyle("A$fila:H$fila")->applyFromArray($styleArray);

    $this->log->logs("********************Fin generarExcelFormatoVacaciones TH***********************");
    $writer = new Xlsx($spreadsheet);
    $response = new StreamedResponse(function () use ($writer)
    {
      $writer->save('php://output');
      flush();
    });
    $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $response->headers->set('Content-Disposition', 'attachment;filename="reporte.xlsx"');
    $response->headers->set('Cache-Control', 'max-age=0');
    return $response;
  }

  public function modificarCcostoAdm(Request $request)
  {
    $this->log->logs("********************Inicia modificarCcostoAdm TH***********************");
    $response = new JsonResponse();
    $content = $request->getContent();
    $respuesta = array();

    if (empty($content))
    {
      $respuesta["code"] = 0;
      $respuesta["datos"] = "No Llegaron los Datos, intente nuevamente";
      return $response->setContent(json_encode($respuesta));
    }

    $jsonContent = json_decode($content, true);
    $ccosto=(!empty($jsonContent['ccosto'])) ? $jsonContent['ccosto'] : null;
    $tipo=(!empty($jsonContent['tipo'])) ? $jsonContent['tipo'] : null;

    if ($tipo == 1)
    {
      $sql = "INSERT INTO th_ccostos_administrativo (ccosto, usuario_reg) VALUES ($ccosto,'$this->nickname') RETURNING id";
      $res_sql=$this->cnn->query('0', $sql);

      if(count($res_sql)<=0)
      {
        $this->log->logs("********************Fin2 modificarCcostoAdm TH***********************");
        $respuesta["code"] = 0;
        $respuesta["datos"] = "Hubo un Error en Consulta, Intente Nuevamente";
        return $response->setContent(json_encode($respuesta));
      }
    }
    else
    {
      $sql = "DELETE FROM th_ccostos_administrativo WHERE ccosto = $ccosto RETURNING id";
      $res_sql=$this->cnn->query('0', $sql);

      if(count($res_sql)<=0)
      {
        $this->log->logs("********************Fin3 modificarCcostoAdm TH***********************");
        $respuesta["code"] = 0;
        $respuesta["datos"] = "Hubo un Error en Consulta, Intente Nuevamente";
        return $response->setContent(json_encode($respuesta));
      }
    }

    $respuesta["code"] = 1;
    $respuesta["datos"] = "Modificacion realizada correctamente";
    $this->log->logs("********************Fin modificarCcostoAdm TH***********************");
    return $response->setContent(json_encode($respuesta));
  }
}