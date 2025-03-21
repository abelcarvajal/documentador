<?php

  namespace App\Controller;

  use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
  use Symfony\Component\HttpFoundation\JsonResponse;
  use Symfony\Component\Routing\Annotation\Route;
  use Symfony\Component\HttpFoundation\Response;
  use Symfony\Component\HttpFoundation\Request;
  use App\Services\Conexion;
  use App\Services\Log;
  use App\Services\ConsultaParametro;
  use App\Services\Herramientas;
  use Symfony\Component\HttpClient\HttpClient;
  use Symfony\Contracts\HttpClient\HttpClientInterface;
  //use Symfony\Contracts\HttpClient\ChunkInterface;

  class AppController extends AbstractController
  {
    private $log;
    private $cnn;
    private $prm;
    private $ruta;
    private $php_auth_user;
    private $php_auth_pw;
    private $http_client;
    private $h_c;
    private $ip;
    //private $http_client2;

    public function __construct(Log $log, Conexion $cnn , ConsultaParametro $prm,HttpClientInterface $http_client,Herramientas $h_c)//, ConsultaParametro $prm EntityManager ManagerRegistry
    {
      $this->log = $log;//null;// \Doctrine\DBAL\DriverManager();//::$em;
      $this->cnn = $cnn;
      $this->prm = $prm;
      $this->ruta = $this->prm->parameter('kernel.project_dir');
      $this->log = new Log('app_sgc','',$this->ruta);
      $this->php_auth_user = null;
      $this->php_auth_pw = null;
      $this->http_client = $http_client;
      $this->h_c = $h_c;
      //$this->http_client2 = $http_client2;
      //$this->prm = $prm;
      //$this->entityManager = $this->getDoctrine()->getManager('gamble70');
      $server_addr = getHostByName(php_uname('n')); //$_SERVER['SERVER_ADDR'];
      $this->ip = "http://$server_addr";
    }

    public function doAuthenticate()
    {  //echo "algo=".$_SERVER['PHP_AUTH_USER'];
      /* var_dump($_SERVER);//['PHP_AUTH_USER']
      die(); */
      /* if (isset($_SERVER['PHP_AUTH_USER']) and isset($_SERVER['PHP_AUTH_PW']))
      { */
        //if ($_SERVER['PHP_AUTH_USER'] == "fidelizacion" && $_SERVER['PHP_AUTH_PW'] == "7d1a5cf3") es es la real pero puede cambiar
        //if ($_SERVER['PHP_AUTH_USER'] == "maquina_sgc" && $_SERVER['PHP_AUTH_PW'] == "5f1a8ed2")
        //$this->log->logs($this->php_auth_user."=="."maquina_sgc". "&&". $this->php_auth_pw." ==". "5f1a8ed2");
        if ($this->php_auth_user == "maquina_sgc" && $this->php_auth_pw == "5f1a8ed2")
        {
          return true;
        }
        else if($this->php_auth_user == "pagina_web" && $this->php_auth_pw == "cbaee6b2")
        {
          return true;
        }
        else
        {
          return false;
        }
      //}
    }

    public function validar_logueo($user)
    {
      $this->log->logs("********************Inicia VALIDAR LOGUEO USUARIO***********************");
      $permiso=1;

      $nickname=trim($user);
      $fecha=date('Y-m-d');
      //$cnn_app_= new conn(0,0); //conexion app pruebas dependiendo de la ubicacion
      $sqmant="SELECT * from users u, users_sesion s
      where u.login='$nickname' and u.id=s.id_user and cast(s.fechai as date)='$fecha' and u.estado='A'";
      $this->log->logs("Consulta Validar Logueo: ".$sqmant);
      //$cnn_app_->conectar();
      $res_man=$this->cnn->query('19', $sqmant);//app $cnn_app_->Execute($sqmant);
      //$cnn_app_->close();
 	    //$val=array();
      if($res_man)
      {
        if(count($res_man)>0)//($res_man->fields!=0)
        {
	          //$this->log->logs("Usuario Logueado : ".$sql);
	          //array_push($val, array("status" => 1,"tipo"=>$res->fields[7]));

          $permiso=0; //usuario logueado
        }
        else
        {
          $permiso=1;//usuario sin loguear en el dia
        }
      }
      else
      {
          //array_push($val, array("status" =>0,"msm"=>"No se pudo validar el servicio, intente nuevamente.","bloqueo_g"=>"A"));
        $permiso=2;//error al consultar el logueo del usuario
      }

      $this->log->logs("********************FINALIZA VALIDAR LOGUEO USUARIO***********************");

      return $permiso;
    }

    public function dec_codigo_barra($codigo,$tercero)
    {
      if($tercero==128)//acueducto
      {
        //$fact=substr($codigo,20,28);
        $fact=substr($codigo,20,8);
      }
      else if($tercero==246)//edesa
      {
        //$fact=substr($codigo,23,31);
        $fact=substr($codigo,23,8);
      }
      else if($tercero==465)//ccv camara de comercio
      {
        //$fact=substr($codigo,20,29);
        $fact=substr($codigo,20,9);
    	}
			else if($tercero==479)//espg
			{
				//$fact=substr($codigo,20,28);
        $fact=substr($codigo,20,8);
				$fact=(Int)$fact;
			}
      else if($tercero==8 || $tercero==129)//emsa y llanogas
      {
	  	  //$fact=substr($codigo,20,30);
        $fact=substr($codigo,20,10);
      }
      else
      {
        $fact=$codigo;
      }
      return $fact;
    }

    public function utf8_converter($array)
    {
      array_walk_recursive($array, function(&$item, $key)
      {
        if(!mb_detect_encoding($item, 'utf-8', true))
        {
          $item = mb_convert_encoding($item,'utf8');//utf8_encode($item);
        }
      });

      return $array;
    }

    public function convertArrayKeysToUtf8(array $array)
    {
      $convertedArray = array();
      foreach($array as $key => $value)
      {
        if(!mb_check_encoding($value, 'UTF-8'))
          $value = mb_convert_encoding($value,'utf8');//utf8_encode($value);
        if(is_array($value))
          $value = $this->convertArrayKeysToUtf8($value);
        $convertedArray[$key] = $value;
      }
      return $convertedArray;
    }

    private function analizar_bloqueo($nickname,$total,$tipo=0)
    {
      $fecha_act=date('Y-m-d');
      $hora_obt=date('H:i:s');
      $val="";
      $va_ent=0;
      $sq_control_="SELECT fechai,fechaf,saldo
      from cem_control_recaudos where usuario='$nickname' and fechai=(select max(fechai)
      from cem_control_recaudos where usuario='$nickname')";
      $control_=$this->cnn->query('19', $sq_control_);//app $cnn_app->Execute($sq_control_);

      if($control_)
      {
        if(count($control_)>0)//($control_->RecordCount()>0)//El usuario no ha alcanzado el tope
        {
          $fechai=$control_[0]['fechai'];//$control_->fields[0];
          $fechaf=$control_[0]['fechaf'];//$control_->fields[1];
          $saldo=$control_[0]['saldo'];//$control_->fields[2];

          if($fechaf==null)
          {
            $sq_control="SELECT id_modulo,min_rec,max_rec,min_ret,max_ret,tope,dias_bloqueo
            from users_parametros_recaudos where cast(fecha_ini as date)<='$fecha_act'
            and fecha_fin is null
            and id_modulo='11' and usuario='$nickname'";

            $control=$this->cnn->query('19', $sq_control);//app $cnn_app->Execute($sq_control);
            if($control)
            {
              if(count($control)>0)//($control->RecordCount()>0)//
              {
                $min_rec=$control[0]['min_rec'];//$control->fields[1];
                $max_rec=$control[0]['max_rec'];//$control->fields[2];
                $min_ret=$control[0]['min_ret'];//$control->fields[3];
                $max_ret=$control[0]['max_ret'];//$control->fields[4];
                $tope=$control[0]['tope'];//$control->fields[5];tope sistema viejo
                if($va_ent==0)
                {
                  $c_v=$this->h_c->comparaFecha($fecha_act,"2024-04-03");//compara la fecha para poner en uso el nuevo sistema

                  if($c_v>=0)
                  {
                    //consulta topes sacados de manager para sistema nuevo
                    $sql0="SELECT cast(a.ma3vincula as numeric) as cedula,
                    sum( a.ma3valdebi) as debito,sum(a.ma3Valcred) as credito, sum(a.ma3Valdebi)-sum(a.ma3Valcred) as Saldo
                    FROM mngmcn_acum3 a
                    WHERE --((a.MA3YEAR*100)+a.MA3MONTH) <= 202312 and
                    a.ma3Cuenta Between '28151550' AND '28151550ZZ' AND a.ma3Empresa = '101'
                    AND trim(a.ma3Vincula) = ltrim('$nickname','CVP')
                    and (a.ma3tpreg = 0 or a.ma3tpreg = 1 or a.ma3tpreg = 2)
                    GROUP BY a.ma3vincula ";
                    $res0=$this->cnn->query('5', $sql0);//manager
                    $tope1=0;//sepone el tope en cero
                    if($res0)
                    {
                      if(count($res0)>0)//($control->RecordCount()>0)//
                      {
                        $tope1=$res0[0]['SALDO'];//trim($res0->fields[1]);//saldo si es negativo tiene cupo si es positivo tiene deuda si es cero no tiene cupo
                      }
                      else
                      {
                        $tope1=null;
                      }
                    }
                    else
                    {
                      $tope1=null;
                    }

                    if(!is_numeric($tope1))//vacio
                    {
                      $tope=500000;//299999;
                      if($nickname=='CV40342629')
                      {
                        $tope=8000000;
                      }
                      else if($nickname=='CV1122117543')
                      {
                        $tope=12000000;
                      }
                      else if($nickname=='CV1121835010')
                      {
                        $tope=10000000;
                      }
                      else if($nickname=='CV40218455')
                      {
                        $tope=1500000;
                      }
                      else if($nickname=='CV40390495')
                      {
                        $tope=1000000;
                      }
                    }
                    else if($tope1>=0)//el usuario no tiene cupo o tiene deuda
                    {
                      $tope=0;
                    }
                    else if($tope1<0)//el usuario tiene cupo para trabajar pero toca pasarlo a positivo
                    {
                      $tope=abs($tope1);
                      if($tope<=300000)
                      {
                        $tope=500000;
                      }
                      if($nickname=='CV40342629')
                      {
                        $tope=10000000;
                      }
                      else if($nickname=='CV1234788445' || $nickname=='CV40332221')
                      {
                        $tope=5000000;
                      }
                      else if($nickname=='CV40390495')
                      {
                        $tope=1000000;
                      }
                    }
                    $this->log->logs("Se Saca del Manager el Tope:".$tope);
                  }

                  $sql_valores="SELECT * from saldos_ventas_tat_sgc('$nickname','$fechai')";
                  $cntrl=$this->cnn->query('0', $sql_valores);//sgc $cnn_->Execute($sql_valores);
                  if($cntrl)
                  {
                    if(count($cntrl)>0)//($cntrl->RecordCount()>0)//
                    {
                      $vta_calc_1=$cntrl[0]['saldos_ventas_tat_sgc'];//$cntrl->fields[0];

                      $fec_ini2=substr(trim($fechai),0,10);
                      if($c_v<0)//si fecha actual es menor a la fecha de control de inicio sistema nuevo
                      {
                        /* $sql_gamble="SELECT sum(r2.ventabruta) as venta from
                        (select r.grupoventas,sum(r.ventabruta) as ventabruta from
                        (select f.grupoventas,sum(f.totalpagado) as ventabruta from formularios f
                        inner join
                        (select case when d.servicio_codigo is null then 73 else d.servicio_codigo end as servicio,  t.tipojuego
                        from detalleincentivos d
                        inner join
                        (select p.codigo_tipojuego as tipojuego from equivalenciasproductos e inner join productos p
                        on e.productogamble=p.codigo group by p.codigo_tipojuego) t
                        on d.codigo_planincentivos='99' and d.codigo_tipojuego=t.tipojuego and fechafinal is null
                        group by case when d.servicio_codigo is null then 73 else d.servicio_codigo end,  t.tipojuego
                        union all
                        select servicio_codigo, tipojuego from gamble_otrosservicios where tipojuego not in ('49','52')
                        and servicio_codigo not in ('151','751','753','7562')
                        group by servicio_codigo, tipojuego) s
                        on f.codigo_tipojuego=s.tipojuego
                        where (to_char(f.fecha, 'YYYY-MM-DD')||' ' ||f.hora)>='$fechai'
                        and f.dat_dto_codla_elaboracion_para='17'
                        and login='$nickname'
                        group by f.grupoventas
                        union all
                        select f.grupoventas,sum(f.totalpagado) as ventabruta from gamble.hist_formularios_tat f
                        inner join
                        (select case when d.servicio_codigo is null then 73 else d.servicio_codigo end as servicio,  t.tipojuego
                        from detalleincentivos d
                        inner join
                        (select p.codigo_tipojuego as tipojuego from equivalenciasproductos e inner join productos p
                        on e.productogamble=p.codigo group by p.codigo_tipojuego) t
                        on d.codigo_planincentivos='99' and d.codigo_tipojuego=t.tipojuego and fechafinal is null
                        group by case when d.servicio_codigo is null then 73 else d.servicio_codigo end,  t.tipojuego
                        union all
                        select servicio_codigo, tipojuego from gamble_otrosservicios where tipojuego not in ('49','52')
                        and servicio_codigo not in ('151','751','753','7562')
                        group by servicio_codigo, tipojuego) s
                        on f.codigo_tipojuego=s.tipojuego
                        where (to_char(f.fecha, 'YYYY-MM-DD')||' ' ||f.hora)>='$fechai'
                        and f.dat_dto_codla_elaboracion_para='17'
                        and login='$nickname'
                        group by f.grupoventas  ) r
                        group by r.grupoventas
                        union all
                        select a.grpvtas_codigo,sum(a.valor) as ventabruta from
                        (select d.servicio_codigo,d.prs_documento,d.grpvtas_codigo,sum(d.valor) as valor from detallevtasotrosproductos  d
                        left join consolidadoventaservicios c
                        on d.prs_documento=c.prs_documento and d.servicio_codigo=c.servicio_codigo and d.fechaventa=c.fechaventa and d.nit=c.nit
                        where (to_char(d.fechaventa,'YYYY-MM-DD')||' '||d.horaventa)>='$fechai' and d.prs_documento=cast(ltrim('$nickname','CVP') as numeric)
                        group by d.servicio_codigo,d.prs_documento,d.grpvtas_codigo
                        union all
                        select c.servicio_codigo,c.prs_documento,c.grpvtas_codigo,sum(c.vtabruta) as valor from consolidadoventaservicios c
                        left join  detallevtasotrosproductos  d
                        on d.prs_documento=c.prs_documento and d.servicio_codigo=c.servicio_codigo and d.fechaventa=c.fechaventa and d.nit=c.nit
                        where to_char(c.fechaapertura,'YYYY-MM-DD HH24:MI:SS')>='$fechai' and to_char(c.fechaapertura,'HH24:MI:SS')!='00:00:00'
                        and c.prs_documento=cast(ltrim('$nickname','CVP') as numeric)
                        and d.prs_documento is null
                        group by c.servicio_codigo,c.prs_documento,c.grpvtas_codigo
                        union all
                        select c.servicio_codigo,c.prs_documento,c.grpvtas_codigo,sum(c.vtabruta) as valor from consolidadoventaservicios c
                        left join  detallevtasotrosproductos  d
                        on d.prs_documento=c.prs_documento and d.servicio_codigo=c.servicio_codigo and d.fechaventa=c.fechaventa and d.nit=c.nit
                        where to_char(c.fechaapertura,'YYYY-MM-DD')>='$fec_ini2' and to_char(c.fechaapertura,'HH24:MI:SS')='00:00:00'
                        and c.prs_documento=cast(ltrim('$nickname','CVP') as numeric)
                        and d.prs_documento is null
                        group by c.servicio_codigo,c.prs_documento,c.grpvtas_codigo) a
                        left join
                        (select case when d.servicio_codigo is null then 73 else d.servicio_codigo end as servicio from detalleincentivos d
                        inner join
                        (select p.codigo_tipojuego as tipojuego from equivalenciasproductos e inner join productos p on e.productogamble=p.codigo
                        group by p.codigo_tipojuego) t
                        on d.codigo_planincentivos='99' and d.codigo_tipojuego=t.tipojuego and d.fechafinal is null
                        group by case when d.servicio_codigo is null then 73 else d.servicio_codigo end
                        union all
                        select distinct servicio_codigo from gamble_otrosservicios where tipojuego not in ('49','52')
                        and servicio_codigo not in ('151','751','753','7562')) s
                        on a.servicio_codigo=s.servicio
                        where s.servicio is null
                        group by a.grpvtas_codigo) r2
                        where r2.grupoventas in ('4','5','58','36','59','60','61','62','65','67','68','69','71')"; */

                        $sql_gamble="SELECT sum(r2.ventabruta) as venta from
                        (select r.grupoventas,sum(r.ventabruta) as ventabruta from
                        (select f.grupoventas,sum(f.totalpagado) as ventabruta from formularios f
                        inner join
                        (select case when d.servicio_codigo is null then 73 else d.servicio_codigo end as servicio,  t.tipojuego
                        from detalleincentivos d
                        inner join
                        (select p.codigo_tipojuego as tipojuego from equivalenciasproductos e inner join productos p
                        on e.productogamble=p.codigo group by p.codigo_tipojuego) t
                        on d.codigo_planincentivos='99' and d.codigo_tipojuego=t.tipojuego and fechafinal is null
                        group by case when d.servicio_codigo is null then 73 else d.servicio_codigo end,  t.tipojuego
                        union all
                        select servicio_codigo, tipojuego from gamble_otrosservicios where tipojuego not in ('49','52')
                        and servicio_codigo not in ('151','751','753','7562')
                        group by servicio_codigo, tipojuego) s
                        on f.codigo_tipojuego=s.tipojuego
                        where (to_char(f.fecha, 'YYYY-MM-DD')||' ' ||f.hora)>='$fechai'
                        and f.dat_dto_codla_elaboracion_para='17'
                        and login='$nickname'
                        group by f.grupoventas
                        union all
                        select f.grupoventas,sum(f.totalpagado) as ventabruta from gamble.hist_formularios_tat f
                        inner join
                        (select case when d.servicio_codigo is null then 73 else d.servicio_codigo end as servicio,  t.tipojuego
                        from detalleincentivos d
                        inner join
                        (select p.codigo_tipojuego as tipojuego from equivalenciasproductos e inner join productos p
                        on e.productogamble=p.codigo group by p.codigo_tipojuego) t
                        on d.codigo_planincentivos='99' and d.codigo_tipojuego=t.tipojuego and fechafinal is null
                        group by case when d.servicio_codigo is null then 73 else d.servicio_codigo end,  t.tipojuego
                        union all
                        select servicio_codigo, tipojuego from gamble_otrosservicios where tipojuego not in ('49','52')
                        and servicio_codigo not in ('151','751','753','7562')
                        group by servicio_codigo, tipojuego) s
                        on f.codigo_tipojuego=s.tipojuego
                        where (to_char(f.fecha, 'YYYY-MM-DD')||' ' ||f.hora)>='$fechai'
                        and f.dat_dto_codla_elaboracion_para='17'
                        and login='$nickname'
                        group by f.grupoventas  ) r
                        group by r.grupoventas
                        union all
                        select a.grpvtas_codigo,sum(a.valor) as ventabruta from
                        (select d.servicio_codigo,d.prs_documento,d.grpvtas_codigo,sum(d.valor) as valor from detallevtasotrosproductos  d
                        where (to_char(d.fechaventa,'YYYY-MM-DD')||' '||d.horaventa)>='$fechai' and d.prs_documento=cast(ltrim('$nickname','CVP') as numeric)
                        group by d.servicio_codigo,d.prs_documento,d.grpvtas_codigo
                        union all
                        select c.servicio_codigo,c.prs_documento,c.grpvtas_codigo,sum(c.vtabruta) as valor from consolidadoventaservicios c
                        left join  detallevtasotrosproductos  d
                        on d.prs_documento=c.prs_documento and d.servicio_codigo=c.servicio_codigo and d.fechaventa=c.fechaventa and d.nit=c.nit
                        and d.sucursal=c.sucursal
                        where to_char(c.fechaapertura,'YYYY-MM-DD HH24:MI:SS')>='$fechai' and to_char(c.fechaapertura,'HH24:MI:SS')!='00:00:00'
                        and c.prs_documento=cast(ltrim('$nickname','CVP') as numeric)
                        and d.prs_documento is null
                        group by c.servicio_codigo,c.prs_documento,c.grpvtas_codigo
                        union all
                        select c.servicio_codigo,c.prs_documento,c.grpvtas_codigo,sum(c.vtabruta) as valor from consolidadoventaservicios c
                        left join  detallevtasotrosproductos  d
                        on d.prs_documento=c.prs_documento and d.servicio_codigo=c.servicio_codigo and d.fechaventa=c.fechaventa and d.nit=c.nit
                        and d.sucursal=c.sucursal
                        where to_char(c.fechaapertura,'YYYY-MM-DD')>='$fec_ini2' and to_char(c.fechaapertura,'HH24:MI:SS')='00:00:00'
                        and c.prs_documento=cast(ltrim('$nickname','CVP') as numeric)
                        and d.prs_documento is null
                        group by c.servicio_codigo,c.prs_documento,c.grpvtas_codigo) a
                        left join
                        (select case when d.servicio_codigo is null then 73 else d.servicio_codigo end as servicio from detalleincentivos d
                        inner join
                        (select p.codigo_tipojuego as tipojuego from equivalenciasproductos e inner join productos p on e.productogamble=p.codigo
                        group by p.codigo_tipojuego) t
                        on d.codigo_planincentivos='99' and d.codigo_tipojuego=t.tipojuego and d.fechafinal is null
                        group by case when d.servicio_codigo is null then 73 else d.servicio_codigo end
                        union all
                        select distinct servicio_codigo from gamble_otrosservicios where tipojuego not in ('49','52')
                        and servicio_codigo not in ('151','751','753','7562')) s
                        on a.servicio_codigo=s.servicio
                        where s.servicio is null
                        group by a.grpvtas_codigo) r2
                        where r2.grupoventas in ('4','5','58','36','59','60','61','62','65','67','68','69','71')";
                      }//fin sistema viejo
                      else//inicia sistema nuevo
                      {
                        /* $sql_gamble="SELECT sum(r2.ventabruta) as venta from
                        (select r.grupoventas,sum(r.ventabruta) as ventabruta from
                        (select f.grupoventas,sum(f.totalpagado) as ventabruta from formularios f
                        inner join
                        (select case when d.servicio_codigo is null then 73 else d.servicio_codigo end as servicio,  t.tipojuego
                        from detalleincentivos d
                        inner join
                        (select p.codigo_tipojuego as tipojuego from equivalenciasproductos e inner join productos p
                        on e.productogamble=p.codigo group by p.codigo_tipojuego) t
                        on d.codigo_planincentivos='99' and d.codigo_tipojuego=t.tipojuego and fechafinal is null
                        group by case when d.servicio_codigo is null then 73 else d.servicio_codigo end,  t.tipojuego
                        union all
                        select servicio_codigo, tipojuego from gamble_otrosservicios where tipojuego not in ('49','52')
                        and servicio_codigo not in ('151','751','753','7562')
                        group by servicio_codigo, tipojuego) s
                        on f.codigo_tipojuego=s.tipojuego
                        where (to_char(f.fecha, 'YYYY-MM-DD')||' ' ||f.hora)>='$fechai'
                        and f.dat_dto_codla_elaboracion_para='17'
                        and login='$nickname'
                        group by f.grupoventas
                        union all
                        select f.grupoventas,sum(f.totalpagado) as ventabruta from gamble.hist_formularios_tat f
                        inner join
                        (select case when d.servicio_codigo is null then 73 else d.servicio_codigo end as servicio,  t.tipojuego
                        from detalleincentivos d
                        inner join
                        (select p.codigo_tipojuego as tipojuego from equivalenciasproductos e inner join productos p
                        on e.productogamble=p.codigo group by p.codigo_tipojuego) t
                        on d.codigo_planincentivos='99' and d.codigo_tipojuego=t.tipojuego and fechafinal is null
                        group by case when d.servicio_codigo is null then 73 else d.servicio_codigo end,  t.tipojuego
                        union all
                        select servicio_codigo, tipojuego from gamble_otrosservicios where tipojuego not in ('49','52')
                        and servicio_codigo not in ('151','751','753','7562')
                        group by servicio_codigo, tipojuego) s
                        on f.codigo_tipojuego=s.tipojuego
                        where (to_char(f.fecha, 'YYYY-MM-DD')||' ' ||f.hora)>='$fechai'
                        and f.dat_dto_codla_elaboracion_para='17'
                        and login='$nickname'
                        group by f.grupoventas  ) r
                        group by r.grupoventas
                        union all
                        select a.grpvtas_codigo,sum(a.valor) as ventabruta from
                        (select d.servicio_codigo,d.prs_documento,d.grpvtas_codigo,sum(d.valor) as valor from detallevtasotrosproductos  d
                        left join consolidadoventaservicios c
                        on d.prs_documento=c.prs_documento and d.servicio_codigo=c.servicio_codigo and d.fechaventa=c.fechaventa and d.nit=c.nit
                        where (to_char(d.fechaventa,'YYYY-MM-DD')||' '||d.horaventa)>='$fechai' and d.prs_documento=cast(ltrim('$nickname','CVP') as numeric)
                        group by d.servicio_codigo,d.prs_documento,d.grpvtas_codigo
                        union all
                        select c.servicio_codigo,c.prs_documento,c.grpvtas_codigo,sum(c.vtabruta) as valor from consolidadoventaservicios c
                        left join  detallevtasotrosproductos  d
                        on d.prs_documento=c.prs_documento and d.servicio_codigo=c.servicio_codigo and d.fechaventa=c.fechaventa and d.nit=c.nit
                        where to_char(c.fechaventa,'YYYY-MM-DD')>='$fec_ini2' --and to_char(c.fechaapertura,'HH24:MI:SS')='00:00:00'
                        and c.prs_documento=cast(ltrim('$nickname','CVP') as numeric)
                        and d.prs_documento is null
                        group by c.servicio_codigo,c.prs_documento,c.grpvtas_codigo) a
                        left join
                        (select case when d.servicio_codigo is null then 73 else d.servicio_codigo end as servicio from detalleincentivos d
                        inner join
                        (select p.codigo_tipojuego as tipojuego from equivalenciasproductos e inner join productos p on e.productogamble=p.codigo
                        group by p.codigo_tipojuego) t
                        on d.codigo_planincentivos='99' and d.codigo_tipojuego=t.tipojuego and d.fechafinal is null
                        group by case when d.servicio_codigo is null then 73 else d.servicio_codigo end
                        union all
                        select distinct servicio_codigo from gamble_otrosservicios where tipojuego not in ('49','52')
                        and servicio_codigo not in ('151','751','753','7562')) s
                        on a.servicio_codigo=s.servicio
                        where s.servicio is null
                        group by a.grpvtas_codigo) r2
                        where r2.grupoventas in ('4','5','58','36','59','60','61','62','65','67','68','69','71')"; */

                        $sql_gamble="SELECT sum(r2.ventabruta) as venta from
                        (select r.grupoventas,sum(r.ventabruta) as ventabruta from
                        (select f.grupoventas,sum(f.totalpagado) as ventabruta from formularios f
                        inner join
                        (select case when d.servicio_codigo is null then 73 else d.servicio_codigo end as servicio,  t.tipojuego
                        from detalleincentivos d
                        inner join
                        (select p.codigo_tipojuego as tipojuego from equivalenciasproductos e inner join productos p
                        on e.productogamble=p.codigo group by p.codigo_tipojuego) t
                        on d.codigo_planincentivos='99' and d.codigo_tipojuego=t.tipojuego and fechafinal is null
                        group by case when d.servicio_codigo is null then 73 else d.servicio_codigo end,  t.tipojuego
                        union all
                        select servicio_codigo, tipojuego from gamble_otrosservicios where tipojuego not in ('49','52')
                        and servicio_codigo not in ('151','751','753','7562')
                        group by servicio_codigo, tipojuego) s
                        on f.codigo_tipojuego=s.tipojuego
                        where (to_char(f.fecha, 'YYYY-MM-DD')||' ' ||f.hora)>='$fechai'
                        and f.dat_dto_codla_elaboracion_para='17'
                        and login='$nickname'
                        group by f.grupoventas
                        union all
                        select f.grupoventas,sum(f.totalpagado) as ventabruta from gamble.hist_formularios_tat f
                        inner join
                        (select case when d.servicio_codigo is null then 73 else d.servicio_codigo end as servicio,  t.tipojuego
                        from detalleincentivos d
                        inner join
                        (select p.codigo_tipojuego as tipojuego from equivalenciasproductos e inner join productos p
                        on e.productogamble=p.codigo group by p.codigo_tipojuego) t
                        on d.codigo_planincentivos='99' and d.codigo_tipojuego=t.tipojuego and fechafinal is null
                        group by case when d.servicio_codigo is null then 73 else d.servicio_codigo end,  t.tipojuego
                        union all
                        select servicio_codigo, tipojuego from gamble_otrosservicios where tipojuego not in ('49','52')
                        and servicio_codigo not in ('151','751','753','7562')
                        group by servicio_codigo, tipojuego) s
                        on f.codigo_tipojuego=s.tipojuego
                        where (to_char(f.fecha, 'YYYY-MM-DD')||' ' ||f.hora)>='$fechai'
                        and f.dat_dto_codla_elaboracion_para='17'
                        and login='$nickname'
                        group by f.grupoventas  ) r
                        group by r.grupoventas
                        union all
                        select a.grpvtas_codigo,sum(a.valor) as ventabruta from
                        (select d.servicio_codigo,d.prs_documento,d.grpvtas_codigo,sum(d.valor) as valor from detallevtasotrosproductos  d
                        where (to_char(d.fechaventa,'YYYY-MM-DD')||' '||d.horaventa)>='$fechai' and d.prs_documento=cast(ltrim('$nickname','CVP') as numeric)
                        group by d.servicio_codigo,d.prs_documento,d.grpvtas_codigo
                        union all
                        select c.servicio_codigo,c.prs_documento,c.grpvtas_codigo,sum(c.vtabruta) as valor from consolidadoventaservicios c
                        left join  detallevtasotrosproductos  d
                        on d.prs_documento=c.prs_documento and d.servicio_codigo=c.servicio_codigo and d.fechaventa=c.fechaventa and d.nit=c.nit
                        and d.sucursal=c.sucursal
                        where to_char(c.fechaventa,'YYYY-MM-DD')>='$fec_ini2' --and to_char(c.fechaapertura,'HH24:MI:SS')='00:00:00'
                        and c.prs_documento=cast(ltrim('$nickname','CVP') as numeric)
                        and d.prs_documento is null
                        group by c.servicio_codigo,c.prs_documento,c.grpvtas_codigo) a
                        left join
                        (select case when d.servicio_codigo is null then 73 else d.servicio_codigo end as servicio from detalleincentivos d
                        inner join
                        (select p.codigo_tipojuego as tipojuego from equivalenciasproductos e inner join productos p on e.productogamble=p.codigo
                        group by p.codigo_tipojuego) t
                        on d.codigo_planincentivos='99' and d.codigo_tipojuego=t.tipojuego and d.fechafinal is null
                        group by case when d.servicio_codigo is null then 73 else d.servicio_codigo end
                        union all
                        select distinct servicio_codigo from gamble_otrosservicios where tipojuego not in ('49','52')
                        and servicio_codigo not in ('151','751','753','7562')) s
                        on a.servicio_codigo=s.servicio
                        where s.servicio is null
                        group by a.grpvtas_codigo) r2
                        where r2.grupoventas in ('4','5','58','36','59','60','61','62','65','67','68','69','71')";

                        $this->log->logs("Entra ha Sacar ventas gamble sistema nuevo:");
                      }//fin sistema nuevo
                      $cntrl_12=$this->cnn->query('2', $sql_gamble);
                      if(count($cntrl_12)>=0)
                      {
                        $vta_calc_2=0;
                        if(!empty($cntrl_12))
                        {
                          $vta_calc_2=$cntrl_12[0]['VENTA'];
                        }


                        $vta_calc=(int)$vta_calc_1+(int)$vta_calc_2+(int)$saldo;
                        $this->log->logs("Valores CONTROL $nickname | vta_calc1: $vta_calc_1 | vta_calc2: $vta_calc_2 | saldo: $saldo | vta_calc/Total: $vta_calc
                        |Transaccion:$total |Tope:$tope");

                        if($tipo==0)
                        {
                          $vta_actual=$vta_calc+$total;
                        }
                        else
                        {
                          $vta_actual=$vta_calc-$total;
                        }
                        if((int)$vta_calc>=(int)$tope)
                        {
                          $sql_up="UPDATE cem_control_recaudos set fechaf=now(),fecha_bloqueo=now(),estado='1',
                          usuario_modifica='CP86069529',fecha_modifica_usuario=now() where usuario='$nickname' and
                          fechai=(select max(fechai) from cem_control_recaudos where usuario='$nickname' and fechaf is null)";
                          $this->cnn->query('19', $sql_up);//app $cnn_app->Execute($sql_up);
                          $val="0|9.Alcanzo el tope, Entregue el dinero para habilitarlo nuevamente|1";
                          $this->log->logs("TOPE ALCANZADO -- BLOQUEADO");
                          $this->log->logs($vta_calc);
                          $this->log->logs($tope);
                        }
                        else
                        {
                          if((int)$vta_actual==(int)$tope)
                          {
                            $this->log->logs("TOPE ALCANZADO -- BLOQUEAR EN LA SIGUIENTE TRANSACCION $nickname");
                          }
                          $this->log->logs("CONTROL ACTUAL $nickname : ".$vta_actual."<=".$tope);
                          $val="1|Puede seguir Operando|0|$min_rec|$max_rec|$min_ret|$max_ret|$vta_actual|$tope|$vta_calc";
                        }
                      }
                      else
                      {
                        $val="0|5.Hubo un problema al validar sus Transacciones_4|1";
                      }
                    }
                    else
                    {
                      $val="0|5.Hubo un problema al validar sus Transacciones_2|1";
                      // array_push($val, array("status" =>0,"msm"=>"5.Hubo un problema al validar sus Consignaciones","bandera" =>1));
                    }
                  }
                  else
                  {
                    $val="0|5.Hubo un problema al validar sus Transacciones|1";
                    // array_push($val, array("status" =>0,"msm"=>"5.Hubo un problema al validar sus Consignaciones","bandera" =>1));
                  }
                }
              }
              else
              {
                $val="0|8.El usuario se encuentra bloqueado, comuniquese con tesoreria|1";
                // array_push($val, array("status" =>0,"msm"=>"8.El usuario no se encuentra parametrizado, comuniquese con su supervisor","bandera" =>1));
              }
            }
            else
            {
              $val="0|5.Hubo un problema al validar su parametrizacion|1";
              // array_push($val, array("status" =>0,"msm"=>"5.Hubo un problema al validar su parametrizacion","bandera" =>1));
            }
          }
          else
          {
            $val="0|10.Alcanzo el tope, Entregue el dinero para habilitarlo nuevamente|1";
          }
        }
        else
        {
           $val="0|10.Su usuario no se encuentra bien parametrizado|1";
                // array_push($val, array("status" =>0,"msm"=>"9.Alcanzo el tope, Entregue el dinero para habilitarlo nuevamente","bandera" =>1));
        }
      }
      else
      {
        $val="0|5.Hubo un problema al validar al validar su estado|1";
            // array_push($val, array("status" =>0,"msm"=>"5.Hubo un problema al validar al validar su tope","bandera" =>1));
      }
      return $val;
    }

    private function validar_estado_usuario($nickname)
    {
      $fecha_act=date('Y-m-d');
      $respuesta = array();
      $respuesta["code"] = 0;

      $sq_control_="SELECT fechai,fechaf,saldo,estado from cem_control_recaudos where usuario='$nickname' and fechai=(select max(fechai) from cem_control_recaudos where usuario='$nickname')";
      $control_=$this->cnn->query('19', $sq_control_);

      if(count($control_)>0)
      {
        $fechaf=$control_[0]['fechaf'];
        $estado=$control_[0]['estado'];
        if($fechaf==null)
        {
          $sq_control="SELECT fecha_fin
          from users_parametros_recaudos where cast(fecha_ini as date)<='$fecha_act' and usuario='$nickname'";

          $control=$this->cnn->query('19', $sq_control);
          if(count($control)>0)
          {
            $fecha_fin=$control[0]['fecha_fin'];
            if($fecha_fin==null)
            {
              $respuesta["code"] = 1;
              $respuesta["message"] = "OK";
            }
            else $respuesta["message"]="8.El usuario se encuentra bloqueado, comuniquese con tesoreria.";
          }
          else $respuesta["message"]="8.Su usuario no se encuentra parametrizado en USER_PARAMETROS, comuniquese con soporte.";
        }
        else if($estado == "3")
        {
          $respuesta["message"]="11.Usuario bloqueado por no tener ventas en los ultimos 15 dias. Valide con tesoreria.";
        }
        else
        {
          $message = "Valide el total con tesoreria.";
          $message2 = "10.Usuario bloqueado en CEM,";
          $res=$this->h_c->saldo_tope_tat($nickname,$this->log);
          $res1 = json_decode($res->getContent());
          if($res1[0]->datos[0][0]!=null)
          {
            $ventas = $res1[0]->datos[0][3];
            $tope = $res1[0]->datos[0][2];
            $total_al_bloquearse = $res1[0]->datos[0][9];
            $message = "Total venta $".number_format($ventas,0,'',',').". Pague la totalidad para su desbloqueo.";
            $message2 = "10.Usuario bloqueado, supero su tope de venta. ";
            if($total_al_bloquearse < $tope) $message2 = "10.Usuario bloqueado, supero los dias para abonar la venta. ";
          }
          $respuesta["message"]="$message2 $message";
        }
      }
      else $respuesta["message"]="10.Su usuario no se encuentra parametrizado en CEM_CONTROL";
      $this->log->logs("respuesta validar_estado_usuario ",array($respuesta));
      return $respuesta;
    }

    //#[Route('/app/validarTransaccionBeMovil', name: 'app_app_validarTransaccionBeMovil', methods: ['POST'])]
    private function validarTransaccionBeMovil($valor,$telefo,$total,$nickname,$fecha_act,$id_ope,$placa,$id_pro,$tipo_r)//Request $request)
    {
      $this->log->logs("********************Inicia Validar Transaccion Bemovil***********************");
      $this->log->logs("valor:".$valor."|telefo:".$telefo."|total:".$total."|nickname:".$nickname."|fecha_act:".$fecha_act."|id_ope:".$id_ope."|placa:".$placa."|id_pro:".$id_pro."|tipo_r:".$tipo_r);
      if($valor!="false" && $valor!="False" && $valor!="FALSE")
      {
        if($valor=="")
        {
          //verificar en la base de datos si la rta es vacia
          //$cnn= new conn(0,1);//conexion sgc
          // $sql1="select r.id,r.fecha_sys
          // from bemovil_recargas_recaudado r,
          // (SELECT MAX(fecha_sys) from bemovil_recargas_recaudado
          // where tel='$telefo' and valor='$total' and usuario='$nickname' and estado='0') f
          // where f.max=r.fecha_sys and r.tel='$telefo' and r.valor='$total' and r.usuario='$nickname' and r.estado='0'";

          if ($tipo_r=="0" || $tipo_r=="1")
          {//Recargas y paquetes
            $sql1="select r.id,r.fecha_sys from bemovil_recargas_recaudado r, (SELECT MAX(fecha_sys) from bemovil_recargas_recaudado
            where tel='$telefo' and valor='$total' and usuario='$nickname' and estado='0' and id_operador='$id_ope') f
            where f.max=r.fecha_sys and r.tel='$telefo' and r.valor='$total' and r.usuario='$nickname' and r.estado='0' and r.id_operador='$id_ope'";
          }
          else if($tipo_r=="2")//runt
          {
            $sql1="select r.id,r.fecha_sys from bemovil_hvehicular_recaudado r, (SELECT MAX(fecha_sys) from bemovil_hvehicular_recaudado
            where dest_correo='$telefo' and valor='$total' and usuario='$nickname' and estado='0' and placa='$placa') f
            where f.max=r.fecha_sys and r.dest_correo='$telefo' and r.valor='$total' and r.usuario='$nickname' and r.estado='0' and r.placa='$placa'";
          }
          else if($tipo_r=="3")//Pines
          {
            $sql1="select r.id,r.fecha_sys from bemovil_pines_recaudado r, (SELECT MAX(fecha_sys) from bemovil_pines_recaudado
            where correo='$telefo' and valor='$total' and usuario='$nickname' and estado='0' and id_pin='$id_pro') f
            where f.max=r.fecha_sys and r.correo='$telefo' and r.valor='$total' and r.usuario='$nickname' and r.estado='0' and r.id_pin='$id_pro'";
          }

          $this->log->logs($sql1);
          $st=$this->cnn->query('0', $sql1);//sgc $cnn->Execute($sql1);
          /* if($st)
          { */
            if(count($st)>0)//($st->fields>0)
            {
              $id_trans=$st[0]['id'];//$st->fields[0];
              //$fecha_trans_aux=explode(".",$st->fields[1]);
              $fecha_trans_aux=explode(".",$st[0]['fecha_sys']);
              $fecha_trans=$fecha_trans_aux[0];
              $segundos = strtotime($fecha_act) - strtotime($fecha_trans);
              $this->log->logs("fecha_act:".$fecha_act."-fecha_trans:".$fecha_trans."|segundos:".$segundos);
              if($segundos>0 and $segundos<=90)
              {
                $valor="0|".$id_trans."|Recarga Exitosa";
              }
              else
              {
                $valor="1|No fue posible realizar la Transaccion, Intente Nuevamente|";
              }
            }
            else
            {
              $valor="1|No se pudo realizar la Transaccion, Intente Nuevamente|";
            }
          /* }
          else
          {
            $valor="1|Error de Consulta SQL, Comuniquese con Soporte|";
          } */
        }
      }
      else
      {
        //$valor="1|Error de Transaccion, Comuniquese con Soporte|";
        $this->log->logs("LA TRANSACCION ENTRO POR ERROR 'FALSE'... CONFIRMANDO RECAUDO BEMOVIL SGC|".$telefo);
        $valor=$this->validarTransaccionBeMovil("",$telefo,$total,$nickname,$fecha_act,$id_ope,$placa,$id_pro,$tipo_r);
      }

      $this->log->logs("********************Termina Validar Transaccion Bemovil***********************");

      return $valor;
      /* //recoger los datos por post
      $json = $request->get('json', null);

      /* var_dump($json);
      die(); */
      // decodigficar el json
      /*$params = json_decode($json);//,true);

      /* var_dump($params);//[0]->id_user);
      die();  */
      //respuesta por defecto
      /*$val=array();
      /* $data =[
        'status' => 'error',
        'code'  => 200,
        'message' => 'Prueba Error Datos',
        'params' => $params
      ];    */
      //array_push($val, array("status" =>1,"msm"=>"Prueba Error Datos","code"=>'200'));

      //$cnn->logs('CP86069529','pruebas');

      /*  var_dump($params);
      die();  */

      //comprobar y validar datos
      /*if($json != null)
      {
        $this->log->logs('Se Reciben los Datos:',$params);
        //$this->log->logs("valor:".$valor."|telefo:".$telefo."|total:".$total."|nickname:".$nickname."|fecha_act:".$fecha_act."|id_ope:".$id_ope."|placa:".$placa."|id_pro:".$id_pro."|tipo_r:".$tipo_r);

        $valor = (!empty($params[0]->valor)) ? $params[0]->valor  : null;
        $telefo = (!empty($params[0]->telefo)) ? $params[0]->telefo  : null;
        $total = (!empty($params[0]->total)) ? $params[0]->total  : null;
        $nickname = (!empty($params[0]->nickname)) ? $params[0]->nickname  : null;
        $fecha_act = (!empty($params[0]->fecha_act)) ? $params[0]->fecha_act  : null;
        $id_ope = (!empty($params[0]->id_ope)) ? $params[0]->id_ope  : null;
        $placa = (!empty($params[0]->placa)) ? $params[0]->placa  : null;
        $id_pro = (!empty($params[0]->id_pro)) ? $params[0]->id_pro  : null;
        $tipo_r = (!empty($params[0]->tipo_r)) ? $params[0]->tipo_r  : "0";

        $this->log = new Log('app_sgc',$nickname,$this->ruta);
        $this->log->logs('Comienza Validar Datos.');

        /*  var_dump($tipo_r);
        die(); */

        /*if(!empty($telefo)  && !empty($total)  && !empty($nickname) )//&& !empty($tipo_r))
        {
          //$sql="select id,cc,nombre,mail,login,(select string_agg(cast(id as text), ',') from app_modulos where estado='0' ) as modules from users where mail='".$user."' and pass='".$pass."'";

          if($valor=="")
          {
            //verificar en la base de datos si la rta es vacia
            //$cnn= new conn(0,1);//conexion sgc

            if ($tipo_r=="0" || $tipo_r=="1")
            {//Recargas y paquetes
              $sql1="select r.id,r.fecha_sys from bemovil_recargas_recaudado r, (SELECT MAX(fecha_sys)
              from bemovil_recargas_recaudado
              where tel='$telefo' and valor='$total' and usuario='$nickname' and estado='0' and id_operador='$id_ope') f
              where f.max=r.fecha_sys and r.tel='$telefo' and r.valor='$total' and r.usuario='$nickname'
              and r.estado='0' and r.id_operador='$id_ope'";
            }
            else if($tipo_r=="2")//runt
            {
              $sql1="select r.id,r.fecha_sys from bemovil_hvehicular_recaudado r, (SELECT MAX(fecha_sys) from bemovil_hvehicular_recaudado
              where dest_correo='$telefo' and valor='$total' and usuario='$nickname' and estado='0' and placa='$placa') f
              where f.max=r.fecha_sys and r.dest_correo='$telefo' and r.valor='$total' and r.usuario='$nickname' and r.estado='0' and r.placa='$placa'";
            }
            else if($tipo_r=="3")//Pines
            {
              $sql1="select r.id,r.fecha_sys from bemovil_pines_recaudado r, (SELECT MAX(fecha_sys) from bemovil_pines_recaudado
              where correo='$telefo' and valor='$total' and usuario='$nickname' and estado='0' and id_pin='$id_pro') f
              where f.max=r.fecha_sys and r.correo='$telefo' and r.valor='$total' and r.usuario='$nickname' and r.estado='0' and r.id_pin='$id_pro'";
            }

            /* var_dump($sql1);
            die(); */

            /*$st=$this->cnn->query('0', $sql1);//sgc $cnn->Execute($sql1);

            /*  var_dump($st);
            die(); */
            /*if($st)
            {
              if(count($st)>0)//($st->fields>0)
              {
                /* var_dump($st[0]);
                die(); */
                /*$id_trans=$st[0]['id'];//$st->fields[0];
                $fecha_trans_aux=explode(".",$st[0]['fecha_sys']);//$st->fields[1]);
                $fecha_trans=$fecha_trans_aux[0];
                $segundos = strtotime($fecha_act) - strtotime($fecha_trans);
                $this->log->logs("fecha_act:".$fecha_act."-fecha_trans:".$fecha_trans."|segundos:".$segundos);
                /* var_dump($segundos);
                die(); */
                /*if($segundos>0 and $segundos<=90)
                {
                  $valor="0|".$id_trans."|Recarga Exitosa";
                  array_push($val, array("status" =>0,"valor" => $valor,"msm"=>"Recarga Exitosa","code"=>200));
                }
                else
                {
                  $valor="1|No fue posible realizar la Transaccion, Intente Nuevamente|";
                  array_push($val, array("status" =>1,"valor" => $valor,"msm"=>"No fue posible realizar la Transaccion, Intente Nuevamente","code"=>200));
                }
              }
              else
              {
                $valor="1|No se pudo realizar la Transaccion, Intente Nuevamente|";
                array_push($val, array("status" =>1,"valor" => $valor,"msm"=>"No se pudo realizar la Transaccion, Intente Nuevamente","code"=>200));
              }
            }
            else
            {
              $valor="1|Error de Consulta SQL, Comuniquese con Soporte|";
              array_push($val, array("status" =>1,"valor" => $valor,"msm"=>"Error de Consulta SQL, Comuniquese con Soporte","code"=>200));
            }
          }
          else
          {
            $valor="1|Ya Tiene un Valor|";
            array_push($val, array("status" =>1,"valor" => $valor,"msm"=>"Ya Tiene un Valor","code"=>200));
          }
        }
        else
        {
          //array_push($val, array("status" =>0,"msm"=>"Alguno de los Datos se Encuentra Vacio.","code"=>200));
          $valor="1|Alguno de los Datos se Encuentra Vacio.|";
          array_push($val, array("status" =>1,"valor" => $valor,"msm"=>"Alguno de los Datos se Encuentra Vacio.","code"=>200));
        }
      /* }
      else
      {
        //array_push($val, array("status" =>0,"msm"=>"No Llegaron los Datos al Sgc.","code"=>200));
        $valor="1|No Llegaron los Datos al Sgc.|";
        array_push($val, array("status" =>1,"valor" => $valor,"msm"=>"No Llegaron los Datos al Sgc.","code"=>200));
      } */
      //return json_encode($val);



      //return new JsonResponse($val);
    }

    public function mailActivarusuario($mail,$token,$fecha_exp)
    {
      $html='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
      <html style="width:100%;font-family:arial, "helvetica neue", helvetica, sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;padding:0;Margin:0;">
       <head>
        <meta charset="UTF-8">
        <meta content="width=device-width, initial-scale=1" name="viewport">
        <meta name="x-apple-disable-message-reformatting">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="telephone=no" name="format-detection">
        <title>con</title>
        <!--[if (mso 16)]>
          <style type="text/css">
          a {text-decoration: none;}
          </style>
          <![endif]-->
        <!--[if gte mso 9]><style>sup { font-size: 100% !important; }</style><![endif]-->
        <style type="text/css">
      @media only screen and (max-width:600px) {p, ul li, ol li, a { font-size:16px!important; line-height:150%!important } h1 { font-size:30px!important; text-align:center; line-height:120%!important } h2 { font-size:26px!important; text-align:center; line-height:120%!important } h3 { font-size:20px!important; text-align:center; line-height:120%!important } h1 a { font-size:30px!important } h2 a { font-size:26px!important } h3 a { font-size:20px!important } .es-menu td a { font-size:16px!important } .es-header-body p, .es-header-body ul li, .es-header-body ol li, .es-header-body a { font-size:16px!important } .es-footer-body p, .es-footer-body ul li, .es-footer-body ol li, .es-footer-body a { font-size:16px!important } .es-infoblock p, .es-infoblock ul li, .es-infoblock ol li, .es-infoblock a { font-size:12px!important } *[class="gmail-fix"] { display:none!important } .es-m-txt-c, .es-m-txt-c h1, .es-m-txt-c h2, .es-m-txt-c h3 { text-align:center!important } .es-m-txt-r, .es-m-txt-r h1, .es-m-txt-r h2, .es-m-txt-r h3 { text-align:right!important } .es-m-txt-l, .es-m-txt-l h1, .es-m-txt-l h2, .es-m-txt-l h3 { text-align:left!important } .es-m-txt-r img, .es-m-txt-c img, .es-m-txt-l img { display:inline!important } .es-button-border { display:block!important } a.es-button { font-size:20px!important; display:block!important; border-width:10px 0px 10px 0px!important } .es-btn-fw { border-width:10px 0px!important; text-align:center!important } .es-adaptive table, .es-btn-fw, .es-btn-fw-brdr, .es-left, .es-right { width:100%!important } .es-content table, .es-header table, .es-footer table, .es-content, .es-footer, .es-header { width:100%!important; max-width:600px!important } .es-adapt-td { display:block!important; width:100%!important } .adapt-img { width:100%!important; height:auto!important } .es-m-p0 { padding:0px!important } .es-m-p0r { padding-right:0px!important } .es-m-p0l { padding-left:0px!important } .es-m-p0t { padding-top:0px!important } .es-m-p0b { padding-bottom:0!important } .es-m-p20b { padding-bottom:20px!important } .es-mobile-hidden, .es-hidden { display:none!important } .es-desk-hidden { display:table-row!important; width:auto!important; overflow:visible!important; float:none!important; max-height:inherit!important; line-height:inherit!important } .es-desk-menu-hidden { display:table-cell!important } table.es-table-not-adapt, .esd-block-html table { width:auto!important } table.es-social { display:inline-block!important } table.es-social td { display:inline-block!important } }
      #outlook a {
          padding:0;
      }
      .ExternalClass {
          width:100%;
      }
      .ExternalClass,
      .ExternalClass p,
      .ExternalClass span,
      .ExternalClass font,
      .ExternalClass td,
      .ExternalClass div {
          line-height:100%;
      }
      .es-button {
          mso-style-priority:100!important;
          text-decoration:none!important;
      }
      a[x-apple-data-detectors] {
          color:inherit!important;
          text-decoration:none!important;
          font-size:inherit!important;
          font-family:inherit!important;
          font-weight:inherit!important;
          line-height:inherit!important;
      }
      .es-desk-hidden {
          display:none;
          float:left;
          overflow:hidden;
          width:0;
          max-height:0;
          line-height:0;
          mso-hide:all;
      }
      .credit-card {
          margin: 0px auto 0;
          border: 0px solid #ddd;
          border-radius: 10px;
          background-color: #fff;
          box-shadow: 1px 2px 3px 0 rgba(0,0,0,.10);
      }
      .fondo
      {
          background: rgb(3,1,36);
          background: radial-gradient(circle, rgba(3,1,36,1) 0%, rgba(12,12,66,1) 48%, rgba(4,52,125,1) 100%);
      }
      </style>
       </head>
       <body >
        <div class="es-wrapper-color" style="background-color:#F6F6F6;">
         <!--[if gte mso 9]>
                  <v:background xmlns:v="urn:schemas-microsoft-com:vml" fill="t">
                      <v:fill type="tile" color="#f6f6f6"></v:fill>
                  </v:background>
              <![endif]-->
         <table cellpadding="0" cellspacing="0" class="es-wrapper" width="100%" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;padding:0;Margin:0;width:100%;height:100%;background-repeat:repeat;background-position:center top;">
           <tr style="border-collapse:collapse;">
            <td valign="top" style="padding:0;Margin:0;">
             <table cellpadding="0" cellspacing="0" class="es-content" align="center" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;table-layout:fixed !important;width:100%;">
               <tr style="border-collapse:collapse;">
                <td align="center" style="padding:0;Margin:0;">
                 <table class="es-content-body" align="center" cellpadding="0" cellspacing="0" width="600" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;background-color:transparent;">
                   <tr style="border-collapse:collapse;">
                    <td align="left" style="Margin:0;padding-top:20px;padding-bottom:20px;padding-left:20px;padding-right:20px;">
                     <!--[if mso]><table width="560" cellpadding="0" cellspacing="0"><tr><td width="356" valign="top"><![endif]-->
                     <table cellpadding="0" cellspacing="0" class="es-left" align="left" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;float:left;">
                       <tr style="border-collapse:collapse;">
                        <td width="356" class="es-m-p0r es-m-p20b" valign="top" align="center" style="padding:0;Margin:0;">
                         <table cellpadding="0" cellspacing="0" width="100%" role="presentation" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;">
                           <tr style="border-collapse:collapse;">
                            <td align="left" class="es-m-txt-c es-infoblock" style="padding:0;Margin:0;line-height:14px;font-size:12px;color:#CCCCCC;"><p style="Margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-size:12px;font-family:arial, "helvetica neue", helvetica, sans-serif;line-height:14px;color:#CCCCCC;">Activacion de cuenta !.Revisa este Correo</p></td>
                           </tr>
                         </table></td>
                       </tr>
                     </table>
                     <!--[if mso]></td><td width="20"></td><td width="184" valign="top"><![endif]-->
                     <table cellpadding="0" cellspacing="0" align="right" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;">
                       <tr style="border-collapse:collapse;">
                        <td width="184" align="left" style="padding:0;Margin:0;">
                         <table cellpadding="0" cellspacing="0" width="100%" role="presentation" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;">
                           <tr style="border-collapse:collapse;">
                            <td align="right" class="es-m-txt-c es-infoblock" style="padding:0;Margin:0;line-height:14px;font-size:12px;color:#CCCCCC;"><p style="Margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-size:12px;font-family:arial, "helvetica neue", helvetica, sans-serif;line-height:14px;color:#CCCCCC;"><a target="_blank" href="https://twitter.com/consuertev?s=03" style="-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, "helvetica neue", helvetica, sans-serif;font-size:12px;text-decoration:underline;color:#2CB543;">Siguenos en @consuertev </a></p></td>
                           </tr>
                         </table></td>
                       </tr>
                     </table>
                     <!--[if mso]></td></tr></table><![endif]--></td>
                   </tr>
                 </table></td>
               </tr>
             </table>
             <table cellpadding="0" cellspacing="0" class="es-content" align="center" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;table-layout:fixed !important;width:100%;">
               <tr style="border-collapse:collapse;">
                <td align="center" style="padding:0;Margin:0;">
                 <table bgcolor="#ffffff" class="es-content-body credit-card" align="center" cellpadding="0" cellspacing="0" width="600" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;background-color:#FFFFFF;">
                   <tr style="border-collapse:collapse;">
                    <td align="left" style="Margin:0;padding-top:20px;padding-bottom:20px;padding-left:20px;padding-right:20px;">
                     <table cellpadding="0" cellspacing="0" width="100%" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;">
                       <tr style="border-collapse:collapse;">
                        <td width="560" align="center" valign="top" style="padding:0;Margin:0;">
                         <table cellpadding="0" cellspacing="0" width="100%" role="presentation" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;">
                           <tr style="border-collapse:collapse;">
                            <td align="left" style="padding:0;Margin:0;padding-bottom:15px;"><h2 style="Margin:0;line-height:29px;mso-line-height-rule:exactly;font-family:arial, "helvetica neue", helvetica, sans-serif;font-size:24px;font-style:normal;font-weight:normal;color:#333333;">Â¡Enhorabuena! Registro Completado </h2></td>
                           </tr>
                           <tr style="border-collapse:collapse;">
                            <td align="center" style="padding:0;Margin:0;font-size:0px;"><a target="_blank" href="https://www.consuerte.com.co/promociones" style="-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, "helvetica neue", helvetica, sans-serif;font-size:14px;text-decoration:underline;color:#1376C8;"><img class="adapt-img" src="https://www.consuerte.com.co/images/promos_banner/BANNERPINES_Mesadetrabajo1.png" alt style="display:block;border:0;outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;" width="560"></a></td>
                           </tr>
                           <tr style="border-collapse:collapse;">
                            <td align="left" style="padding:0;Margin:0;padding-top:20px;"><p style="Margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-size:14px;font-family:arial, "helvetica neue", helvetica, sans-serif;line-height:21px;color:#333333;"><a href="https://www.consuerte.com.co/api/activacion_cuenta_test?token='.$token.'&fecha_exp='.$fecha_exp.'">Clic aqui para activar la cuenta</a> <br><br></p></td>
                           </tr>
                           <tr style="border-collapse:collapse;">
                            <td align="left" style="padding:0;Margin:0;padding-top:15px;">

                             </td>
                           </tr>

                         </table></td>
                       </tr>
                     </table></td>
                   </tr>
                 </table></td>
               </tr>
             </table>
             <table cellpadding="0" cellspacing="0" class="es-footer" align="center" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;table-layout:fixed !important;width:100%;background-color:transparent;background-repeat:repeat;background-position:center top;">
               <tr style="border-collapse:collapse;">
                <td align="center" style="padding:0;Margin:0;">
                 <table class="es-footer-body" align="center" cellpadding="0" cellspacing="0" width="600" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;background-color:transparent;">
                   <tr style="border-collapse:collapse;">
                    <td align="left" style="Margin:0;padding-top:20px;padding-bottom:20px;padding-left:20px;padding-right:20px;">
                     <table cellpadding="0" cellspacing="0" width="100%" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;">
                       <tr style="border-collapse:collapse;">
                        <td width="560" align="center" valign="top" style="padding:0;Margin:0;">
                         <table cellpadding="0" cellspacing="0" width="100%" role="presentation" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;">
                           <tr style="border-collapse:collapse;">
                            <td align="center" style="padding:20px;Margin:0;font-size:0;">
                             <table border="0" width="75%" height="100%" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;">
                               <tr style="border-collapse:collapse;">
                                <td style="padding:0;Margin:0px 0px 0px 0px;border-bottom:1px solid #CCCCCC;background:none;height:1px;width:100%;margin:0px;"></td>
                               </tr>
                               <tr>
                                  <td><center>
                                  <a target="_blank" href="https://www.consuerte.com.co" style="-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-family:arial, "helvetica neue", helvetica, sans-serif;font-size:12px;text-decoration:underline;color:#2CB543;"><img src="https://www.consuerte.com.co/images/logo_consuerte_final.png" alt width="125" style="display:block;border:0;outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;"></a></center>
                                  </td>
                               </tr>
                             </table></td>
                           </tr>
                           <tr style="border-collapse:collapse;">
                            <td align="center" style="padding:0;Margin:0;padding-top:10px;padding-bottom:10px;"><p style="Margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-size:11px;font-family:arial, "helvetica neue", helvetica, sans-serif;line-height:17px;color:#333333;">
                            Ã‚Â© Consuerte S.A '.date('Y').' - Todos los derechos reservados<br>
                            <a href="https://www.consuerte.com.co/politica.pdf">PolÃƒÂ­tica de tratamiento de datos personales</a><br>
                            <a href="https://www.consuerte.com.co/terminos.pdf">TÃƒÂ©rminos y Condiciones </a>
                            </p></td>
                           </tr>
                         </table></td>
                       </tr>
                     </table></td>
                   </tr>
                 </table></td>
               </tr>
             </table>
             <table cellpadding="0" cellspacing="0" class="es-content" align="center" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;table-layout:fixed !important;width:100%;">
               <tr style="border-collapse:collapse;">
                <td align="center" style="padding:0;Margin:0;">
                 <table class="es-content-body" align="center" cellpadding="0" cellspacing="0" width="600" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;background-color:transparent;">
                   <tr style="border-collapse:collapse;">
                    <td align="left" style="padding:0;Margin:0;padding-left:20px;padding-right:20px;padding-bottom:30px;">
                     <table cellpadding="0" cellspacing="0" width="100%" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;">
                       <tr style="border-collapse:collapse;">
                        <td width="560" align="center" valign="top" style="padding:0;Margin:0;">
                         <table cellpadding="0" cellspacing="0" width="100%" role="presentation" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:collapse;border-spacing:0px;">
                           <tr style="border-collapse:collapse;">
                            <td class="es-infoblock es-m-txt-c" align="center" style="padding:0;Margin:0;line-height:0px;font-size:0px;color:#CCCCCC;"></td>
                           </tr>
                         </table></td>
                       </tr>
                     </table></td>
                   </tr>
                 </table></td>
               </tr>
             </table></td>
           </tr>
         </table>
       </body>
      </html>';
      $sPara=$mail;
      $sAsunto="Consuerte S.A - Activación de Cuenta";
      $sDe="soporte@consuerte.com.co";
      //$sTexto=$html;
      //$sTexto .="\nPara ver el historial de propietarios solo tienes que dar <a href='$url2' >CLICKK AQUI</a>\n\n";
      //$pdf_generado=creaPdf($sTexto);//ruta

      //$sTexto2="En HoraBuena!! \n\n\n ";
      //$sTexto .="Nit  -  Codigo Servicio  -  Nombre \n\n";
      /*for($x=0; $x<count($vec2); $x++)
      {
        $sTexto .=$vec2[$x][0]."  -  ".$vec2[$x][1]."  -  ".$vec2[$x][2]." \n";
      }*/
      //$cabeceras = "From: <la_dire_de_email@gmail.com>\n";
      if ($sDe)$cabeceras = "From:".$sDe."\n";
      else $cabeceras = "";
      //$cabeceras .= "Reply-To: ".$sDe."\r\nBcc: jsbarrerao@consuerte.com.co\n";
      $cabeceras .= "Reply-To: ".$sDe."\r\nBcc: rdiaz@consuerte.com.co, masuarezr@consuerte.com.co\n";
      $cabeceras .= "MIME-version: 1.0\n";
      $cabeceras .= "Content-type:multipart/mixed;";
      $cabeceras .= "boundary=\"Message-Boundary\"\n";
      $cabeceras .= "Content-transfer-encoding: 7BIT\n";
      $cabeceras .= "X-attachments: fichero.bin";

      $body_top = "--Message-Boundary\n";
      $body_top .= "Content-type: text/html; charset=UTF-8\n";
      $body_top .= "Content-transfer-encoding: 7BIT\n";
      $body_top .= "Content-description: Mail message body\n\n";

      //$cuerpo = $body_top.$sTexto.;
      $cuerpo = $body_top.$html;

      $fecha_imp=date('Y_m_d_H_i_s');
      //$nom_cer='recaudo_asesora_'.$fecha_imp.'.pdf';
      //archivos adjuntos
      /*
      $cuerpo .= "\n\n--Message-Boundary\n";
      $cuerpo .= "Content-type: Binary; name=\"".$nom_cer."\"\n";
      $cuerpo .= "Content-Transfer-Encoding: BASE64\n";
      $cuerpo .= "Content-disposition: attachment; filename=\"".$nom_cer."\"\n\n";

      $file = fopen($pdf_generado, "r");
      $contenido_ma = fread($file, filesize($pdf_generado));
      $encoded_attach = chunk_split(base64_encode($contenido_ma));
      fclose($file);

      $cuerpo .= "$encoded_attach\n";
      $cuerpo .= "--Message-Boundary--\n";

      */
      mail($sPara,$sAsunto,$cuerpo,$cabeceras);
      /*
      if(mail($sPara,$sAsunto,$cuerpo,$cabeceras))
      {
        return true;
      }
      else
      {
        return false;
      }*/

    }

    public function envioActivacionmail($mail)
    {
      //$cnn_app_= new conn(0,0); //conexion app pruebas dependiendo de la ubicacion
      $token=sha1($mail.date("Y-m-d H:i:s"));
      //$fecha_i=date(DATE_ATOM, mktime(date("H"), date("i"), date("s"),date("m"), date("d"), date("Y")));
      //$fecha_ex=date(DATE_ATOM, mktime(date("H"), date("i")+30, date("s"),date("m"), date("d"), date("Y")));

      $fecha_i=date("Y-m-d H:i:s");
      $fecha_ex=strtotime ('30 minute' , strtotime ($fecha_i) ) ;
      $fecha_ex = date ( 'Y-m-d H:i:s' , $fecha_ex);
      $sqmant="insert into users_activacion (mail,token,fecha_ini,fecha_expira)
      values('".$mail."','".$token."','".$fecha_i."','".$fecha_ex."')";
      $this->log->logs($sqmant);
      //$cnn_app_->conectar();
      $res_man=$this->cnn->query('19', $sqmant);//app $cnn_app_->Execute($sqmant);
      //$cnn_app_->close();
      if($res_man)
      {
        $this->mailActivarusuario($mail,$token,$fecha_ex);
      }
    }

    /* #[Route('/app', name: 'app_app', methods: ['POST'])]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/AppController.php',
        ]);
    } */

    function horas_permitidas($log)
    {
      $sq_horario="select * from datos where id in ('3','4') and estado='0' order by id asc";
      $res_horario=$this->cnn->query('19', $sq_horario);//app
      if(!(count($res_horario)>0))
        return false;

      $horarios = array();
      $cont_val = 0;
      $log->logs("HORARIO ",$res_horario);
      foreach ($res_horario as $row)
      {
        $horarios[$cont_val]=$row['descripcion'];
        $cont_val++;
      }

      if($cont_val != 2)
        return false;

      // $hora1 = strtotime( "07:00" );
      // $hora2 = strtotime( "23:59:59" );
      $hora1 = strtotime($horarios[0]);
      $hora2 = strtotime($horarios[1]);
      $hora_act = strtotime(date("H:i:s"));
      $log->logs("********************VALIDACION DE HORARIO***********************");
      $log->logs("hora1 $hora1 hora2 $hora2 hora_act $hora_act estado ".($hora_act > $hora1 && $hora_act < $hora2));
      return ($hora_act > $hora1 && $hora_act < $hora2);
    }

    #[Route('/app/validatelogin', name: 'app_app_validatelogin', methods: ['POST'])]
    public function validatelogin(Request $request)//: JsonResponse
    {
      /* $this->log = new Log('app_sgc','',$this->ruta);  */
     /*  var_dump($this->prm->parameter('kernel.project_dir'));
      die(); */
      $this->log->logs('Inicia Proceso ValidateLogin');
      $json = $request->get('json', null);
      $params = json_decode($json);//,true);
      $data=array();

      if(!($this->horas_permitidas($this->log)))
      {
        $data =[
          'status' => '0',//'error',
          'code'  => 200,
          'msm' => "Horario habilitado para transacciones es de 07:00AM a 11:59PM",
          'bloqueo_g' => 'A'
        ];
        $this->log->logs("data: ",$data);
        return new JsonResponse(array($data));
        exit;
      }

      //comprobar y validar datos
      if($json != null)
      {
        $this->log->logs('Se Reciben los Datos:',$params);
        $user = (!empty($params[0]->user)) ? $params[0]->user  : null;
        $pass = (!empty($params[0]->pass)) ? $params[0]->pass  : null;
        $token = (!empty($params[0]->token)) ? $params[0]->token  : null;
        $imeis = (!empty($params[0]->imeis)) ? $params[0]->imeis  : null;
        $ips = (!empty($params[0]->ips)) ? $params[0]->ips : null;
        $name_device = (!empty($params[0]->namedevice)) ? $params[0]->namedevice  : null;
        $tipoLogin = (!empty($params[0]->tipologin)) ? $params[0]->tipologin  : null;
        $huella = (!empty($params[0]->huella)) ? $params[0]->huella  : null;
        $operador = (!empty($params[0]->operador)) ? $params[0]->operador  : null;
        $VERSION_CODE = (!empty($params[0]->VERSION_CODE)) ? $params[0]->VERSION_CODE  : null;

        $this->log = new Log('app_sgc',$user,$this->ruta);
        if(empty($VERSION_CODE))
        {
          $data =[
            'status' => '-2',
            'code'  => 200,
            'bloqueo_g' => 'A',
            'msm' => 'Actualice la version del App'
          ];
          $this->log->logs("data: ",$data);
          return new JsonResponse(array($data));
        }

        $sql_con="select max(version) as version from app_version";
        $res_ver=$this->cnn->query('19', $sql_con);
        if(!count($res_ver)>0)
        {
          $data =[
            'status' => '-2',
            'code'  => 200,
            'bloqueo_g' => 'A',
            'msm' => 'No se pudo validar la version'
          ];
          $this->log->logs("data: ",$data);
          return new JsonResponse(array($data));
        }

        $vers=$res_ver[0]['version'];//$res->fields[0];
        /* if(!($vers==$VERSION_CODE))
        {
          $data =[
            'status' => '-2',
            'code'  => 200,
            'bloqueo_g' => 'A',
            'msm' => 'Su App no esta actualizada'
          ];
          $this->log->logs("data: ",$data);
          return new JsonResponse(array($data));
        } */

        if(!empty($user) && !empty($pass) && !empty($token))
        {
          if($token=="b586ef9772f7e23075a393b4ac87eea9")
          {
            $sqmant="select * from datos where id='2' and estado='1'";
            $res_man=$this->cnn->query('19', $sqmant);//app
            $val=array();

            if(count($res_man)==0)//($res_man->fields==0)
            {
              $this->log->logs("token ".$token." user ".$user." pass ".$pass." tipo login ".$tipoLogin." ".$huella);
              $r=0;// 0=false 1=true
              $msm_login="";
              $id_huella="0";
              $sql="";
              if($tipoLogin=="1" || $tipoLogin=="2" )//si el logeo es por medio de huelal android o del cs10
              {
                $sql="select u.id,u.cc,u.nombre,u.mail,u.login,(select string_agg(cast(s.id as text), ',')
                from (select id,excluir from app_modulos where estado='0' and permisos like '%,'||u.tipo_user||',%' ) s
                where s.excluir not like '%,'||u.mail||',%' or s.excluir is null )as modules,
                (select firebase from firebaseid where id_users=u.mail  order by fecha_sys desc limit 1) as token,
                u.tipo_user,u.apellido,u.tel,(select string_agg(cast(id as text), ',')
                from parametros_pagos where estado='0' )as pasarelas_pagos,
                u.fecha_nac from users u where u.mail='".$user."' and u.estado='A'";

                if($huella=="")
                {
                  $huella="null";
                }
                else
                {
                  $huella="'".$huella."'";
                }
                $r_finger="insert into users_huellas (code_finger,tipo_huella,estado)values(".$huella.",'".$tipoLogin."','0')returning id;";
                $r_id_huell=$this->cnn->query('19', $r_finger); //$cnn_->Execute($r_finger);
                $id_huella=$r_id_huell[0]['id'];//$r_id_huell->fields[0];
              }
              else
              {
                $sql="SELECT u.id,u.cc,u.nombre,u.mail,u.login,(select string_agg(cast(s.id as text), ',') from (
                select id,excluir from app_modulos where estado='0' and permisos like '%,'||u.tipo_user||',%' )s
                where s.excluir not like '%,'||u.mail||',%' or s.excluir is null )as modules,
                (select firebase from firebaseid where id_users=u.mail  order by fecha_sys desc limit 1) as token,
                u.tipo_user,u.apellido,u.tel,(select string_agg(cast(id as text), ',')
                from parametros_pagos where estado='0' )as pasarelas_pagos,u.fecha_nac
                from users u where u.mail='".$user."' and u.pass='".$pass."' and u.estado='A'";
              }
              $res=$this->cnn->query('19', $sql);//app $cnn_->Execute($sql);
              $val_status_gamble="A";

              if(count($res)>0)//($res->fields>0)
              {
                $select_s="select h.id_user,h.token,h.imeis,h.ips,h.name_device ,c.code_finger,c.id
                from users_sesion h left join users_huellas c on c.id=h.id_huella and  c.id='".$id_huella."' and estado='0'
                where h.id_user='".$res[0]['id']."' and h.fechaf is null and h.imeis is not null and h.name_device is not null";

                $se_val=$this->cnn->query('19', $select_s);//app $cnn_->Execute($select_s);
                $succes=false;
                $imei_after="";
                $ips_after="";
                $device_after="";
                $huella_cs10="";
                if($se_val)//($se_val)
                {
                  if(count($se_val)>0)//($se_val->fields>0)
                  {
                    $imei_after=$se_val[0]['imeis'];//$se_val->fields[2];
                    $ips_after=$se_val[0]['ips'];//$se_val->fields[3];
                    $device_after=$se_val[0]['name_device'];//$se_val->fields[4];
                    $huella_cs10=$se_val[0]['code_finger'];//$se_val->fields[5];
                    $this->log->logs("validando se_val: ".$device_after."==".$name_device." && ".$imei_after."==".$imeis);
                    if($device_after==$name_device && $imei_after==$imeis)//tanto imeis como nombre de dispositivos deben ser iguales
                    {
                      $succes=true;
                    }
                    else
                    {
                      $msm_login="Hay una Sesion activa en Otro Dispositivo (".$device_after.").";
                    }
                  }
                  else
                  {
                    $succes=true;
                  }
                }
                else
                {
                  $succes=true;
                  $msm_login="Hubo un problema con el servicio, vuelva a intentar.";
                }
                if($res[0]['tipo_user']=="2" || $res[0]['tipo_user']=="4")
                {
                  $sql_mac="SELECT (select estado from usuarios where loginusr='".$user."') as estado_user,
                  m2.DIRECCION_MAC_EQUIPO from mac_punto_venta m2 where (m2.puntoventa,m2.fechasys) in
                  (select m.puntoventa,max(fechasys) as fecha from MAC_PUNTO_VENTA m
                  where m.PUNTOVENTA=(SELECT  distinct c.hraprs_ubcneg_trtrio_codigo as punto
                  from controlhorariopersonas c,contratosventa c2
                  where (c.login,c.cal_dia,c.hhentrada) in( select c4.login,c4.cal_dia,max(c4.hhentrada) as hhentrada
                  from controlhorariopersonas c4,
                  (select c3.login, max(c3.cal_dia) as cal_dia from controlhorariopersonas c3 where c3.login='".$user."'
                  group by c3.login ) v
                  where c4.login=v.login
                  and  c4.cal_dia=v.cal_dia
                  group by c4.login,c4.cal_dia) and  c2.login=c.login and c2.fechafinal is null) and m.estado='A'
                  group by m.puntoventa)";
                  $vs=$this->cnn->query('2', $sql_mac);//gamble 70 $cnn2->Execute($sql_mac);
                  if($vs)
                  {
                    if(count($vs)>0)//($vs->fields>0)
                    {
                      $carpeta = $_ENV['APP_ENV'];
                      $val_status_gamble = "A";
                      if ($carpeta == "prod") $val_status_gamble=$vs[0]['ESTADO_USER'];
                      $mac_gamble=$vs[0]['DIRECCION_MAC_EQUIPO'];//$vs->fields[1];
                      $carpeta=$_ENV['APP_ENV'];//basename(getcwd());
                      if($carpeta=="prod")//"ws_app" || $carpeta=="root")
                      {
                        $this->log->logs("produccion : comparando ".$imeis." | ".$mac_gamble);
                        $pos = strpos($imeis,$mac_gamble);
                      }
                      else//si es pruebas permitir
                      {
                        $this->log->logs("pruebas : comparando ".$mac_gamble." | ".$mac_gamble);
                        $pos = strpos($mac_gamble,$mac_gamble);
                      }

                      if($pos===false)//no se encontro coincidencias de macs
                      {
                        $succes=false;
                        $msm_login="La mac o Imei no coincide, comuniquese con soporte.";
                        $val_status_gamble="";
                      }
                    }
                    else//NO ESTA ACTIVO O NO EXISTE
                    {
                      $val_status_gamble="";
                      $succes=false;
                      $msm_login="No se encontro Usuario en gamble.";
                    }
                  }
                  else//NO EXISTE
                  {
                    $val_status_gamble="";
                    $succes=false;
                    $msm_login="Ocurrio un problema al validar el estado, intente nuevamente.";
                  }
                }

                if($succes)
                {
                  $res_val_usu = $this->validar_estado_usuario($user);
                  if($res_val_usu["code"] == "1")
                  {
                    $sql_update="update users_sesion set fechaf='now()' where id_user='".$res[0]['id']."' and fechaf is null";
                    $sesion=md5($pass.$res[0]['mail'].date('Y-m-d H:i:s'));//passwordcorreofecha
                    $sql_sesion="";
                    if($imeis!="" && $ips!="" && $name_device!="" && $tipoLogin!="")//las nueva versiones
                    {
                      $sql_sesion="insert into users_sesion (id_user,token,fechai,imeis,ips,name_device,tipo_login,id_huella,operator)
                      values('".$res[0]['id']."','".$sesion."','now()','".$imeis."','".$ips."','".$name_device."','".$tipoLogin."',
                      '".$id_huella."','".$operador."');";
                    }
                    else
                    {
                      $sql_sesion="insert into users_sesion (id_user,token,fechai)values('".$res[0]['id']."','".$sesion."','now()');";
                    }

                    $this->cnn->query('19', $sql_update);//$cnn_->Execute($sql_update);
                    $this->cnn->query('19', $sql_sesion);//$cnn_->Execute($sql_sesion);

                    $data =[
                        'status' => '1',
                        "id" => $res[0]['id'],
                        "cc" => $res[0]['cc'],
                        "names"=>$res[0]['nombre'],
                        "mail"=>$res[0]['mail'],
                        "login"=>$res[0]['login'],
                        "modules"=>$res[0]['modules'],
                        "firebaseid"=>$res[0]['token'],
                        "sesion"=>$sesion,
                        "tipo"=>$res[0]['tipo_user'],
                        "apellido"=>$res[0]['apellido'],
                        "tel"=>$res[0]['tel'],
                        "pasarelas"=>$res[0]['pasarelas_pagos'],
                        "fecha_nac"=>$res[0]['fecha_nac'],
                        "bloqueo_g"=>$val_status_gamble,
                        "huella_cs10"=>$huella_cs10,
                        'code'  => 200,
                        'message' => 'Logueo Satisfatorio'
                    ];
                  }
                  else
                  {
                    $val_status_gamble="";
                    $data =[
                      'status' => '0',//'error',
                      'code'  => 200,
                      'msm' => $res_val_usu["message"],
                      'bloqueo_g' => $val_status_gamble
                    ];
                  }
                }
                else
                {
                  $data =[
                      'status' => '0',
                      'code'  => 200,
                      'msm' => $msm_login,
                      'bloqueo_g' => $val_status_gamble
                  ];
                }
              }
              else
              {
                //array_push($val, array("status" => 0,"msm"=>"Error no existe el usuario o pass incorrecto","bloqueo_g"=>$val_status_gamble));
                $data =[
                  'status' => '0',//'error',
                  'code'  => 200,
                  'msm' => "Error no existe el usuario o pass incorrecto",
                  'bloqueo_g' => $val_status_gamble
                ];
              }
            }
            else
            {
              $data =[
                  'status' => '-1',//'error',
                  'code'  => 200,
                  'msm' => 'Servicios en mantenimiento, intente mas tarde...',
                  'bloqueo_g' => 'A'
              ];
            }
          }
          else
          {
            $data =[
                'status' => '0',//'error',
                'code'  => 200,
                'msm' => 'El token es incorrecto',
                'bloqueo_g' => 'B'
            ];
          }
        }
        else
        {
          $data =[
              'status' => '0',
              'code'  => 200,
              'message' => 'Variables Incompletas'
          ];
        }
      }
      else
      {
          $data =[
              'status' => '0',//'success',
              'code'  => 200,
              'message' => 'No llegaron los Datos',
              'params' => $params
          ];
      }

      $this->log->logs("data: ",$data);
      return new JsonResponse(array($data));
    }

    #[Route('/app/newTokenFirebase', name: 'app_app_newTokenFirebase', methods: ['POST'])]
    public function newTokenFirebase(Request $request)
    {
      $this->log->logs("********************Inicia Update TOKEN FIREBASE***********************");
      //recoger los datos por post
      $json = $request->get('json', null);

      /* var_dump($json);
      die(); */
      // decodigficar el json
      $params = json_decode($json);//,true);

      /* var_dump($params);//[0]->id_user);
      die();  */
      //respuesta por defecto
      $val=array();
      /* $data =[
        'status' => 'error',
        'code'  => 200,
        'message' => 'Prueba Error Datos',
        'params' => $params
      ];    */
      //array_push($val, array("status" =>1,"msm"=>"Prueba Error Datos","code"=>'200'));

      //$cnn->logs('CP86069529','pruebas');
      //die();

      //comprobar y validar datos
      if($json != null)
      {
        $this->log->logs('Se Reciben los Datos:',$params);
        $id_user = (!empty($params[0]->id_user)) ? $params[0]->id_user  : null;
        $token = (!empty($params[0]->token)) ? $params[0]->token  : null;

        $this->log = new Log('app_sgc',$id_user,$this->ruta);
        $this->log->logs('Comienza Validar Datos.');

        if(!empty($id_user)  && !empty($token))
        {
          //$sql="select id,cc,nombre,mail,login,(select string_agg(cast(id as text), ',') from app_modulos where estado='0' ) as modules from users where mail='".$user."' and pass='".$pass."'";
          $sql="insert into firebaseid (firebase,id_users)values('".$token."','".$id_user."');";
          //$cnn_= new conn(0,0); //conexion app pruebas dependiendo de la ubicacion
          //$cnn_->conectar();
          $res=$this->cnn->query('19', $sql);//app//$res=$cnn_->Execute($sql);
          //$cnn_->close();

          //var_dump($res);
          if($res)
          {
            array_push($val, array("status" =>1,"msm"=>"Token Actualizado correctamente.","code"=>200));
          }
          else
          {
            array_push($val, array("status" =>0,"msm"=>"No se pudo actualizar el token.","code"=>200));
          }
          $this->log->logs("return ".json_encode($val));
        }
        else
        {
          array_push($val, array("status" =>0,"msm"=>"Alguno de los Datos se Encuentra Vacio.","code"=>200));
        }
      }
      else
      {
        array_push($val, array("status" =>0,"msm"=>"No Llegaron los Datos al Sgc.","code"=>200));
      }
      //return json_encode($val);
      $this->log->logs("********************Termina Update TOKEN FIREBASE***********************");

      return new JsonResponse($val);
    }

    //#[Route('/app/metodos', name: 'app_app_metodos', methods: ['POST'])]
    public function metodos(Request $request)
    {
      $this->log->logs("********************Inicia Metodos***********************");
      $json = $request->get('json', null);
      $params = json_decode($json);//,true);
      $val=array();
      $fecha_act=date("Y-m-d");
      $c_v=$this->h_c->comparaFecha($fecha_act,"2024-04-03");//compara la fecha para poner en uso el nuevo sistema

      if(!($this->horas_permitidas($this->log)))
      {
        array_push($val, array("status" =>0,"msm"=>"Horario habilitado para transacciones es de 07:00AM a 11:59PM"));
        $this->log->logs("val: ",array($val));
        return new JsonResponse($val);
      }

      //comprobar y validar datos
      if($json != null)
      {
        $this->log->logs('Se Reciben los Datos:',$params);

        $con = (!empty($params[0]->con)) ? $params[0]->con  : null;
        $array = (!empty($params[0]->array)) ? $params[0]->array  : null;
        $VERSION_CODE = (!empty($params[0]->VERSION_CODE)) ? $params[0]->VERSION_CODE  : null;
        $usu_env = (!empty($params[0]->usu_env)) ? $params[0]->usu_env  : null;
        $this->php_auth_user = (!empty($params[0]->php_auth_user)) ? $params[0]->php_auth_user  : null;
        $this->php_auth_pw = (!empty($params[0]->php_auth_pw)) ? $params[0]->php_auth_pw  : null;

        $dt = explode("|",$array);
        $this->log = new Log('app_sgc',$dt[0],$this->ruta);

        if(!empty($con)  && !empty($array) )//&& !empty($tipo_r))
        {
          $fechapeticion = date('Y-m-d');
          $horapeticion = date('H:i:s');
          //ESTE CODIGO SE VA A UTILIZAR PARA VALIDAR EL TIPO DE USUARIO PARA VALIDAR LA VERSION, TIPOU 5 NO SE EVALUA PORQUE ES LA MAQUINA
          $datos_validar_tipo_user=explode("|", $array);
          switch ($con)
          {
            case '3':
              $tipouser_validar=$datos_validar_tipo_user[3];//tipo usuario 1=cliente 2=vendedora sgc 5= kiosco_sgc
              break;
            case '4':
              $tipouser_validar=$datos_validar_tipo_user[6];
              break;
            case '24':
              $tipouser_validar=$datos_validar_tipo_user[4];
              break;
            case '27':
            case '28':
              $tipouser_validar="5";
              break;
            default:
              $tipouser_validar="2";
              break;
          }

          $this->log->logs("++++++++++++++++++++++++++++++++++++++++++++++++ con: $con +++++++++++++++++++++++++++++++++++++++++++++++++++++");

          if($con=="5" && $datos_validar_tipo_user[0]=="6")
          {
            $tipouser_validar=$datos_validar_tipo_user[0];
          }
          elseif($con=="6" && $datos_validar_tipo_user[7]=="6")
          {
            $tipouser_validar=$datos_validar_tipo_user[7];
          }

          if(((!empty($usu_env) && $usu_env == "APP_CP") || ($tipouser_validar!="5" && $tipouser_validar!="6") ) && $con!="8")
          {
            if(empty($VERSION_CODE))
            {
              $data =[
                'status' => '-2',
                'code'  => 200,
                'bloqueo_g' => 'A',
                'msm' => 'No se envio informacion de la version del App'
              ];
              $this->log->logs("data: ",$data);
              return new JsonResponse(array($data));
            }

            $sql_con="select max(version) as version from app_version";
            $res_ver=$this->cnn->query('19', $sql_con);
            if(!count($res_ver)>0)
            {
              $data =[
                'status' => '-2',
                'code'  => 200,
                'bloqueo_g' => 'A',
                'msm' => 'No se pudo validar la version'
              ];
              $this->log->logs("data: ",$data);
              return new JsonResponse(array($data));
            }

            $vers=$res_ver[0]['version'];//$res->fields[0];
            /* if(!($vers==$VERSION_CODE))
            {
              $data =[
                'status' => '-2',
                'code'  => 200,
                'bloqueo_g' => 'A',
                'msm' => 'Su App no esta actualizada'
              ];
              $this->log->logs("data: ",$data);
              return new JsonResponse(array($data));
            } */
          }

          $sqmant="select * from datos where id='2' and estado='1'";
          $res_man=$this->cnn->query('19', $sqmant);//$cnn_app_->Execute($sqmant);
          if($con=="8")//ultima version app consuerte pay funciona
          {
            $datos=explode("|", $array);
            $dispo_v=$datos[0];
            $sql_con="select max(version) as version from app_version";
            $res=$this->cnn->query('19', $sql_con);//app $cnn_->Execute($sql_con);
            if($res)
            {
              $vers=$res[0]['version'];//$res->fields[0];
              array_push($val, array("status" =>1,"version"=>$vers));
              /* if($vers==$dispo_v)
              {
                array_push($val, array("status" =>1,"version"=>$vers));
              }
              else
              {
                array_push($val, array("status" =>0,"msm"=>"Se debe Actualizar"));
              } */
            }
            else
            {
              array_push($val, array("status" =>0,"msm"=>"No se pudo validar la version,por el momento sigue usando la misma."));
            }
          }

          if(count($res_man)==0)//($res_man->fields==0)
          {
            if($con=="1")// si es registrar funciona validar envio correo
            {
              //$cnn_= new conn(0,0); //conexion app pruebas dependiendo de la ubicacion
              $dato=explode("|", $array);
              $nombres=$dato[0];
              $apellidos=$dato[1];
              $mail=$dato[2];
              $pass=$dato[3];

              $sqlec="select mail from users where mail='".$mail."'";
              //$cnn_->conectar();
              $res=$this->cnn->query('19', $sqlec);//app $cnn_->Execute($sqlec);
              //$cnn_->close();
              if(count($res)>0)//($res->fields>0)//ya existe el correo
              {
                array_push($val, array("status" =>0,"msm"=>"Ya existe una Cuenta con el correo ".$mail));
              }
              else// no existe el correo se puede insertar
              {
                $inser="insert into users (nombre,apellido,pass,mail)values('".$nombres."','".$apellidos."',md5('".$pass."'),'".$mail."')";
                $res2=$this->cnn->query('19', $inser);//app $cnn_->Execute($inser);
                if($res2)
                {
                  array_push($val, array("status" =>1,"msm"=>"Usuario Creado Correctamente."));
                }
                else
                {
                  array_push($val, array("status" =>0,"msm"=>"No se puedo crear. Intente mas Tarde."));
                }
              }
            }
            else if($con=="2")// retornar convenios activos //funciona
            {
              $this->log->logs("*******INICIA PROCESO LISTA CONVENIOS*******");
              $inf_ws=explode("|", $array);
              if(count($inf_ws)>1)
              {
                $sql_con="select * from mod_convenios where estado='0' and ver_ini<='".$inf_ws[1]."'";
              }
              else
              {
                $sql_con="select * from mod_convenios where estado='0' and ver_ini<='27'";
              }
              $r=$this->cnn->query('19', $sql_con);//app $cnn_->Execute($sql_con);

              if($r)
              {
                if(count($r)>0)//($r->fields>0)
                {
                  foreach ($r as $row => $link)
                  {
                    array_push($val, array("status" =>1,"siglas"=>$link['siglas'],"razon"=>$link['razon'],"nit"=>$link['nit'],"id_tercero"=>$link['id_tercero']));
                  }
                }
                else
                {
                  array_push($val, array("status" =>0,"msm"=>"No Se encontraron Convenios."));
                }
              }
              else
              {
                array_push($val, array("status" =>0,"msm"=>"No Se encontraron Convenios."));
              }
              $this->log->logs("*******FIN PROCESO LISTA CONVENIOS*******");
            }
            else if($con=="3")//consulta convenios //funciona
            {
              $this->log->logs("*******INICIA PROCESO CONSULTA FACTURA*******");
              $datos=explode("|", $array);
              $nickname=$datos[0];
              $usuario=$nickname;
              $tercero=$datos[1];//tercero
              $factura=$datos[2];//factura
              $tipouser=$datos[3];//tipo usuario 1=cliente 2=vendedora sgc 5= kiosco_sgc
              $llano_arreglo=array();
              $control=0;//para llanogas
              $estado2="";
              $permiso=0;
              $llan_ws = null;

              if($tipouser=="5")
              {
                if(!$this->doAuthenticate())
                {
                  array_push($val, array("status" =>0,'msm' => "Invalido Usuario o Password Webservice"));

                  //return "Invalid username or password";
                  $permiso=1;
                  //return json_encode($val);
                }
                else
                {
                  if($tercero!="129" && $tercero!="199")//llnogas=129$tercero!="246" && $tercero!="199")//diferente de edesa y congente
                  {
                    $factura=$this->dec_codigo_barra($factura,$tercero);
                  }
                  $control_nic=$this->validar_logueo($nickname);

                  if($control_nic=="0")
                  {
                    $permiso=0;
                  }
                  else if($control_nic=="1")
                  {
                    array_push($val, array("status" =>0,'msm' => "Su Usuario No Se Ha Logueado. Por Favor Logueese."));
                    $permiso=1;
                  }
                  else
                  {
                    array_push($val, array("status" =>0,'msm' => "Error al Consultar Logueo del Usuario. Por favor Intentelo de Nuevo."));
                    $permiso=1;
                  }
                }
              }
              if($permiso==0)
              {
                $val_llano=0;
                $val_bio=0;
                $fac_bio=0;
                $id_fac_llan="0";
                $id_fac_bio="0";
                $valida_factura=0;
                $homologa="0";
                $veri_llano=null;
                $veri_bio=null;
                $fac_llano=null;
                $fecha_llano=null;
                $fecha_v=null;
                $fac_bio=null;
                $fecha_bio=null;
                $fecha_v=null;
                if($tercero=="8")//emsa
                {
                  $tabla="emsa_regis_detalle";
                }
                else if($tercero=="129")//llanogas ahora ws
                {
                  $this->log->logs("Barras de llanogas: ".$factura);
                  $codebar=explode(",", $factura);
                  $tabla="llanogas_regis_detalle_ws";
                  $homologa="0";
                  $id_fac_llan="0";
                  $id_fac_bio="0";
                  $valida_factura=0;
                  if($codebar[0]!="")//continene barra de llanogas
                  {
                    $ean_llano=$codebar[0];
                    $veri_llano=(int)substr($ean_llano, 10,6);//tipoconvenio
                    $val_llano=(int)substr($ean_llano, 34,10);//valor codigo barras
                    $fac_llano=(int)substr($ean_llano, 20,10);//factura
                    $factura=$fac_llano;
                    $fecha_llano=substr($ean_llano, 46,8);
                    $ano=substr($fecha_llano, 0,4);
                    $mes=substr($fecha_llano, 4,2);
                    $dia=substr($fecha_llano,6,2);
                    $fecha_v=$ano."-".$mes."-".$dia;
                    $tipo_conv="322";//tipo llano
                    if($val_llano>0)
                    {
                      array_push($llano_arreglo, array("fact_"=>$fac_llano,"fecha_"=>$fecha_llano,"valor_"=>$val_llano,"ean_"=>$ean_llano,"conv_"=>$tipo_conv,"fecha_v"=>$fecha_v,"homolo"=>$homologa,"id_reg_llano"=>$id_fac_llan,"id_reg_bio"=>$id_fac_bio));
                    }
                    else
                    {
                      $valida_factura++;
                    }
                  }
                  if(count($codebar)>1)
                  {
                    if($codebar[1]!="")//continene barra de bioagricola
                    {
                      $ean_bio=$codebar[1];
                      $veri_bio=substr($ean_bio, 10,6);//tipoconvenio
                      $val_bio=(int)substr($ean_bio, 34,10);//valor codigo barras
                      $fac_bio=(int)substr($ean_bio, 20,10);
                      $factura=$fac_bio;
                      $fecha_bio=(int)substr($ean_bio, 46,8);
                      $tipo_conv="317";//tipo bio
                      $ano=substr($fecha_bio, 0,4);
                      $mes=substr($fecha_bio, 4,2);
                      $dia=substr($fecha_bio,6,2);
                      $fecha_v=$ano."-".$mes."-".$dia;
                      //$val_bio=0;
                      if($val_bio>0)
                      {
                        array_push($llano_arreglo, array("fact_"=>$fac_bio,"fecha_"=>$fecha_bio,"valor_"=>$val_bio,"ean_"=>$ean_bio,"conv_"=>$tipo_conv,"fecha_v"=>$fecha_v,"homolo"=>$homologa,"id_reg_llano"=>$id_fac_llan,"id_reg_bio"=>$id_fac_bio));
                      }
                      else
                      {
                        $valida_factura++;
                      }

                    }
                  }
                  if($valida_factura>=2)
                  {
                    $control=1;
                  }
                  else if(count($llano_arreglo)==0)//facturas en cero no no hay codigoas de barras
                  {
                    $control=1;
                  }
                  $llan_ws=json_encode($llano_arreglo);
                  $this->log->logs("Datos serializados llanogas codigo empresa:".$veri_llano.", valor llano: ".$val_llano." , factura llano : ".$fac_llano." , fecha llano :".$fecha_llano." fecha vi ".$fecha_v);
                  $this->log->logs("Datos serializados bio codigo empresa:".$veri_bio.", valor bio: ".$val_bio." , factura bio : ".$fac_bio." , fecha bio :".$fecha_bio." fecha vi bio: ".$fecha_v );

                }
                else if($tercero=="128")//acueducto
                {
                  $tabla="acueducto_regis_detalle";
                }
                else if($tercero=="246")//edesa
                {
                  $tabla="edesa_regis_detalle";
                }
                else if($tercero=="199")//congente
                {
                  $tabla="congente_regis_detalle";
                }

                $con=9.2;
                $especial=0;
                $app_ws="1";
                if($control==0)
                {
                  if($tipouser=="2" || $tipouser=="1" || $tipouser=="4" || $tipouser=="5")//usuario tipo vendedoras publicos y tenderos pueden consultar
                  {
                    try
                    {
                      $carpeta=$_ENV['APP_ENV'];//basename(getcwd());
                      if($carpeta=="prod")//"ws_app")
                      {
                        $url="http://10.1.1.4/consuerteinventarios/datos.php";
                      }
                      else//es pruebas
                      {
                        $url=$this->ip."/consuertepruebas/datos.php";
                      }

                      $extras="?con=".$con."&nickname=".$nickname."&tercero=".$tercero."&factura=".$factura."&usuario=".$nickname."&tabla=".$tabla."&especial=".$especial."&ws_app=".$app_ws."&llano_ws=".$llan_ws;
                      $this->log->logs("VARIABLES A DATOS.HP : ".$url.$extras);

                      $response = $this->http_client->request('GET', $url.$extras);//'https://api.github.com/repos/symfony/symfony-docs');
                      $statusCode = $response->getStatusCode();
                      $valor=null;
                      if($statusCode == "200")
                      {
                        $contentType = $response->getHeaders()['content-type'][0];
                        $valor = $response->getContent();
                        $this->log->logs("Resultado Emsa SGC WS : ".$valor);

                        $dat_ws=explode("|",$valor);
                        if($dat_ws[0]==1)//si existe
                        {
                          $val_comi=null;
                          $iva_comi=null;
                          if($tercero=="8")// si es emsa
                          {
                            $status=$dat_ws[0];
                            $totalvista=$dat_ws[1];
                            $totalint=$dat_ws[7];
                            $vence=$dat_ws[2];
                            $id_regist=$dat_ws[5];
                            $titular=mb_convert_encoding($dat_ws[8],'ISO-8859-1');//utf8_decode($dat_ws[8]);
                            $estado=$dat_ws[3];
                            $estado2=$dat_ws[4];
                            //$msm=$dat_ws[1];
                          }
                          else if($tercero=="129")// si es llanogas
                          {
                            //normal //1|0136005005001|HERMANAS CLARIZA      |$39,130.00|2018-11-30|V|VENCIDO|3237584|39130|$42,977.00|42977|$82,107.00
                            //ws//1|000|61676|A|PENDIENTE||||322
                            $status=$dat_ws[0];
                            $homologa=$dat_ws[1];
                            $id_reg_llano=$dat_ws[2];
                            $estado0_llano=$dat_ws[3];
                            $estado1_llano=$dat_ws[4];
                            $id_recaudo_bio=$dat_ws[5];
                            $estado0_bio=$dat_ws[6];
                            $estado1_bio=$dat_ws[7];
                            $tipo_tra=trim($dat_ws[8]);
                            $totalint=$val_llano+$val_bio;
                            $totalvista=number_format($totalint,0,"",",");
                            $vence="Vencimiento :".$fecha_v;
                            $estado="V";
                            $titular="Ref.Pago ".$fac_llano."-".$fac_bio;
                            if($estado0_llano=="A" || $estado0_bio=="A")
                            {
                              $estado="A";
                            }
                            if($tipo_tra=="322")
                            {
                              $id_regist=$id_reg_llano;
                            }
                            else if($tipo_tra=="317")
                            {
                              $id_regist=$id_recaudo_bio;
                            }
                            if($homologa=="008" && ($ean_llano=="" || $ean_bio=="" ))//si es hologada y retorno algun id vacio
                            {
                              //consulta el id para validar el homolgado
                              $status=0;
                              $msm="Por favor Ingrese todos los codigos de barras";
                            }
                            /*$totalvista=$dat_ws[11];

                            $vence=$dat_ws[4];
                            $id_regist=$dat_ws[7];
                            $titular=trim(utf8_decode($dat_ws[2]));
                            $estado=$dat_ws[5];
                            $valorbiovista=$dat_ws[9];
                            $valorbioint=$dat_ws[10];
                            $totalgasint=$dat_ws[8];
                            $totalint=(int)$totalgasint+(int)$valorbioint;*/
                          }
                          else if($tercero=="128")//acueducto
                          {
                            //1|142703|$81,210.00|2020-04-18|A|PENDIENTE|7207809|81210
                            $status=$dat_ws[0];
                            $totalvista=$dat_ws[2];
                            $vence=$dat_ws[3];
                            $estado=$dat_ws[4];
                            $estado2=$dat_ws[5];
                            $id_regist=$dat_ws[6];
                            $titular="CODIGO USUARIO: ".trim($dat_ws[1]);
                            $totalint=(int)$dat_ws[7];
                            /*
                            $titular=trim($dat_ws[2]);
                            $valorbiovista=$dat_ws[9];
                            $valorbioint=$dat_ws[10];
                            $totalgasint=$dat_ws[8];
                            $totalint=(int)$totalgasint+(int)$valorbioint;*/
                          }
                          else if($tercero=="246")//edesa
                          {
                            //1|10119100700101|$2,077.00|2020-04-29|A|PENDIENTE|935441|0|2077|I|3000|-923|$3,000.00|$-923.00
                            //1|10119100700101|$1,100,000.00|2022-11-30|A|PENDIENTE|935441|1|1100000|A|48195|1051805|$48,195.00|$1,051,805.00|BUITRAGO JORGE ERNESTO
                            $status=$dat_ws[0];
                            //$totalvista=$dat_ws[2];
                            $totalvista=$dat_ws[13];
                            $vence=$dat_ws[3];
                            $estado=$dat_ws[4];
                            $estado2=$dat_ws[5];
                            $id_regist=$dat_ws[6];
                            $permisos=$dat_ws[7];
                            //$totalint=(int)$dat_ws[8];
                            $totalint=(int)$dat_ws[11];
                            $titular=mb_convert_encoding($dat_ws[14],'ISO-8859-1');//utf8_decode($dat_ws[14]);//"Nï¿½ FACTURA: ".trim($dat_ws[1]);

                          }
                          else if($tercero=="199")//congente
                          {
                            //1|CORDOBA ZUï¿½IGA ANCIZAR|$1,087,393|2020-04-21|A|PENDIENTE|11302784|1087393|10766|0
                            $status=$dat_ws[0];
                            $totalvista=$dat_ws[2];
                            $vence=$dat_ws[3];
                            $estado=$dat_ws[4];
                            $estado2=$dat_ws[5];
                            $id_regist=$dat_ws[6];
                            $titular=mb_convert_encoding($dat_ws[1],'ISO-8859-1');//utf8_decode($dat_ws[1]);//"Nï¿½ FACTURA: ".trim($dat_ws[1]);
                            $totalint=(int)$dat_ws[7];
                            $val_comi=(int)$dat_ws[8];
                            $iva_comi=(int)$dat_ws[9];

                          }
                          if($status==1)
                          {
                            array_push($val, array("status" =>$status,"totalvista"=>$totalvista,"totalint"=>$totalint,"vence"=>$vence,"id_regist"=>$id_regist,"titular"=>$titular,"estado"=>$estado,"comi"=>$val_comi,"iva"=>$iva_comi,"msm"=>"Factura ".$estado2));
                          }
                          else
                          {
                            array_push($val, array("status" =>$status,"msm"=>$msm,"estado"=>"V"));
                          }
                        }
                        else//no existe
                        {
                          $status=$dat_ws[0];
                          $msm=$dat_ws[1];

                          if($dat_ws[1]==" " || $dat_ws[1]=="")
                          {
                            $msm="No se encontraron resultados , intente mas tarde." ;
                          }
                          array_push($val, array("status" =>$status,"msm"=>$msm,"estado"=>"V"));
                        }
                      }
                      else
                      {
                        $msm="Error Conexion Consulta: ".$response->getContent();//$e->getMessage();
                        $this->log->logs($msm,$response->getInfo('debug'));
                        array_push($val, array("status" =>0,"msm"=>$msm,"estado"=>"V"));
                      }//fin llamada res consulta
                    }
                    catch(\Exception $e)
                    {
                      $msm="Catch ".$e->getMessage();
                      $this->log->logs($msm);
                      array_push($val, array("status" =>0,"msm"=>$msm,"estado"=>"V"));
                    }
                  }
                  else// tipo usuarios diferentes avendedoras
                  {
                    $rest='datos_ws.php';//para pagos de clientes normales
                    array_push($val, array("status" =>0,"msm"=>"Aun no esta habilitado para otros clientes.","estado"=>"V"));
                  }
                }
                else
                {
                  array_push($val, array("status" =>0,"msm"=>"Valores en cero($0) o no hay facturas disponibles.","estado"=>"V"));
                }
              }
              $this->log->logs("RESPUESTA: ", array($val));
              $this->log->logs("*******FIN PROCESO CONSULTA FACTURA*******");
            }
            else if($con=="4")// pagar convenios //funciona
            {
              $this->log->logs("*******INICIA PROCESO PAGAR CONVENIOS*******");
              $datos=explode("|", $array);
              $nickname=$datos[0];
              $tercero=$datos[1];//siempre debe ser emsa
              $factura=$datos[2];
              $facturaid=$datos[3];
              $valorre=$datos[5];
              $tipou=$datos[6];
              $imei=$datos[7];
              $valor_redondeado=null;
              if($tipou==5)
              {
                $valor_redondeado=$datos[8];
              }
              else
              {
                $valor_redondeado="0";
              }

              $especial=0;
              $con=9.3;// es el con de pago...
              $ws_app="1";
              $bandera=0;//0=permite consultar
              $nickname_cliente=$nickname;
              $permiso=0;
              unset($info);
              $info=[];

              if($tipou=="5")
              {
                if(!$this->doAuthenticate())
                {
                  //array_push($val, array("status" =>0,'msm' => "Invalido Usuario o Password Webservice"));

                  //return "Invalid username or password";
                  $permiso=1;
                  $info[0]=0;
                  $info[1]="Invalido Usuario o Password Webservice";
                  //return json_encode($val);
                }
                else
                {
                  if($tercero!="129" && $tercero!="199")//llanogas = 129$tercero!="246" && $tercero!="199")//diferente de edesa y cogente
                  {
                    $factura=$this->dec_codigo_barra($factura,$tercero);
                  }
                  $permiso=0;
                  $info[0]=1;
                }
              }
              else//para vendedora tipo=2
              {
                if($c_v<0)//si fecha actual es menor a la fecha de control de inicio sistema viejo
                {
                  $resultados=$this->analizar_bloqueo($nickname,$valorre);
                  $info=explode("|",$resultados);
                }
                else//sistema nuevo
                {
                  $permiso=0;
                  // $info[0]=1;
                  // $info[1]="Entra a Tratar de Recaudar";
                  $res_val_usu = $this->validar_estado_usuario($nickname);
                  $info[0] = $res_val_usu["code"];
                  $info[1] = $res_val_usu["message"];
                }//fin sistema nuevo
              }

              if($info[0]==1)
              {
                if($tipou<"5" )//si tipo usuario es menor a 5
                {
                  if($c_v<0)//si fecha actual es menor a la fecha de control de inicio sistema viejo
                  {
                    $vta_actual=$info[7];
                    $tope=$info[8];
                    $vta_calc=$info[9];
                    if((int)$vta_actual<=(int)$tope)//si total(recaudado+transaccion) es menor o igual al tope asignado al usuario
                    {
                      if($permiso==0)
                      {
                        if($tipou=="2" )//si es nivel vendedora
                        {
                          $sql1="SELECT  distinct c.hraprs_ubcneg_trtrio_codigo as punto,c.ubcntrtrio_codigo_compuesto_de as ccostos,c.ubcntrtrio_codigo_compuesto__1 as zona
                          from controlhorariopersonas c,contratosventa c2,usuarios c3
                          where (c.login,c.cal_dia,c.hhentrada) in( select c4.login,c4.cal_dia,max(c4.hhentrada) as hhentrada from controlhorariopersonas c4,
                          (select c3.login, max(c3.cal_dia) as cal_dia from controlhorariopersonas c3 where c3.login='".$nickname."'  group by c3.login ) v
                          where c4.login=v.login
                          and  c4.cal_dia=v.cal_dia
                          group by c4.login,c4.cal_dia) and  c2.login=c.login and c2.fechafinal is null and c3.loginusr=c.login and c3.estado='A'";
                          $st=$this->cnn->query('0', $sql1);//sgc $cnn->Execute($sql1);
                          if($st)
                          {
                            if(count($st)>0)//($st->fields>0)
                            {
                              $pdv_r=$st[0]['punto'];//$st->fields[0];
                              $sql_autoriza="SELECT id_tercero from emsa_asignacion_punto
                              where punto_venta='".$pdv_r."' and id_tercero='".$tercero."' and estado='A' ";
                              $res_autoriza=$this->cnn->query('0', $sql_autoriza);//sgc $cnn->Execute($sql_autoriza);
                              if($res_autoriza)
                              {
                                if(count($res_autoriza)>0)//($res_autoriza->fields>0)
                                {
                                  $bandera=0;
                                }
                                else
                                {
                                  $bandera=1;
                                  array_push($val, array("status" =>0,"msm"=>"4.Su Punto de venta no esta autorizado para este convenio."));
                                }
                              }
                              else
                              {
                                $bandera=1;
                                array_push($val, array("status" =>0,"msm"=>"3.No se pudo validar la autorizacion Intenta nuevamente."));
                              }
                            }
                            else
                            {
                              $bandera=1;
                              array_push($val, array("status" =>0,"msm"=>"1.Debe revisar su contrato o esta bloqueado.Contacte a soporte"));
                            }
                          }
                          else
                          {
                            $bandera=1;
                            array_push($val, array("status" =>0,"msm"=>"2.Debe revisar su contrato o esta bloqueado.Contacte a soporte"));
                          }
                        }
                        else if($tipou=="1" || $tipou=="4")//cliente naturales o publicas y tenderos
                        {
                          $nickname_cliente="CV21240220";
                          $val_billetera="select valor from v_billetera_usuario where usuario='".$nickname."'";

                          $res_bille=$this->cnn->query('19', $val_billetera);//app $cnn_app->Execute($val_billetera);
                          if(count($res_bille)>0)//($res_bille->fields>0)
                          {
                            $saldo=(int)$res_bille[0]['valor'];//$res_bille->fields[0];
                          }
                          else
                          {
                            $saldo=0;
                          }
                          if($saldo-(int)$valorre>=0)
                          {
                            //no ahcer anda
                          }
                          else
                          {
                            $bandera=1;
                            array_push($val, array("status" =>0,"msm"=>"Uy, no te alcanza. Intenta Recargar mas saldo."));
                          }
                        }
                        else
                        {
                          $bandera=1;
                          array_push($val, array("status" =>0,"msm"=>"PARA ESTE TIPO DE USUARIO NO ESTA HABILITADO LOS RECAUDOS."));
                        }
                      }
                      else
                      {
                        $bandera=1;
                        array_push($val, array("status" =>0,"msm"=>"6.La Recarga supera el monto permitido, su saldo es $".number_format((int)($tope-$vta_calc),0,'',',')));
                      }
                    }
                    else
                    {
                      $bandera=1;
                      // array_push($val, array("status" =>0,"msm"=>$info[1]));
                      array_push($val, array("status" =>0,"msm"=>"6.El recaudo supera el monto permitido, su saldo es $".number_format((int)($tope-$vta_calc),0,'',',')));
                    }
                  }
                  else//sistema nuevo
                  {
                    if($permiso==0)
                    {
                      if($tipou=="2" )//si es nivel vendedora
                      {
                        $sql1="SELECT  distinct c.hraprs_ubcneg_trtrio_codigo as punto,c.ubcntrtrio_codigo_compuesto_de as ccostos,c.ubcntrtrio_codigo_compuesto__1 as zona
                        from controlhorariopersonas c,contratosventa c2,usuarios c3
                        where (c.login,c.cal_dia,c.hhentrada) in( select c4.login,c4.cal_dia,max(c4.hhentrada) as hhentrada from controlhorariopersonas c4,
                        (select c3.login, max(c3.cal_dia) as cal_dia from controlhorariopersonas c3 where c3.login='".$nickname."'  group by c3.login ) v
                        where c4.login=v.login
                        and  c4.cal_dia=v.cal_dia
                        group by c4.login,c4.cal_dia) and  c2.login=c.login and c2.fechafinal is null and c3.loginusr=c.login and c3.estado='A'";
                        $st=$this->cnn->query('0', $sql1);//sgc $cnn->Execute($sql1);
                        if($st)
                        {
                          if(count($st)>0)//($st->fields>0)
                          {
                            $pdv_r=$st[0]['punto'];//$st->fields[0];
                            $sql_autoriza="SELECT id_tercero from emsa_asignacion_punto
                            where punto_venta='".$pdv_r."' and id_tercero='".$tercero."' and estado='A' ";
                            $res_autoriza=$this->cnn->query('0', $sql_autoriza);//sgc $cnn->Execute($sql_autoriza);
                            if($res_autoriza)
                            {
                              if(count($res_autoriza)>0)//($res_autoriza->fields>0)
                              {
                                $bandera=0;
                              }
                              else
                              {
                                $bandera=1;
                                array_push($val, array("status" =>0,"msm"=>"4.Su Punto de venta no esta autorizado para este convenio."));
                              }
                            }
                            else
                            {
                              $bandera=1;
                              array_push($val, array("status" =>0,"msm"=>"3.No se pudo validar la autorizacion Intenta nuevamente."));
                            }
                          }
                          else
                          {
                            $bandera=1;
                            array_push($val, array("status" =>0,"msm"=>"1.Debe revisar su contrato o esta bloqueado.Contacte a soporte"));
                          }
                        }
                        else
                        {
                          $bandera=1;
                          array_push($val, array("status" =>0,"msm"=>"2.Debe revisar su contrato o esta bloqueado.Contacte a soporte"));
                        }
                      }
                      else if($tipou=="1" || $tipou=="4")//cliente naturales o publicas y tenderos
                      {
                        $nickname_cliente="CV21240220";
                        $val_billetera="select valor from v_billetera_usuario where usuario='".$nickname."'";

                        $res_bille=$this->cnn->query('19', $val_billetera);//app $cnn_app->Execute($val_billetera);
                        if(count($res_bille)>0)//($res_bille->fields>0)
                        {
                          $saldo=(int)$res_bille[0]['valor'];//$res_bille->fields[0];
                        }
                        else
                        {
                          $saldo=0;
                        }
                        if($saldo-(int)$valorre>=0)
                        {
                          //no ahcer anda
                        }
                        else
                        {
                          $bandera=1;
                          array_push($val, array("status" =>0,"msm"=>"Uy, no te alcanza. Intenta Recargar mas saldo."));
                        }
                      }
                      else
                      {
                        $bandera=1;
                        array_push($val, array("status" =>0,"msm"=>"PARA ESTE TIPO DE USUARIO NO ESTA HABILITADO LOS RECAUDOS."));
                      }
                    }
                    else
                    {
                      $bandera=1;
                      //array_push($val, array("status" =>0,"msm"=>"6.La Recarga supera el monto permitido, su saldo es $".number_format((int)($tope-$vta_calc),0,'',',')));
                    }

                  }//fin sistema nuevo
                }
                if($bandera==0)//permite consultar y pagar
                {
                  $sql="SELECT u.id from users u where u.mail='".$nickname."' and u.estado='A'";
                  $res_usu=$this->cnn->query('19', $sql);//app $cnn_app->Execute($sql);
                  if(count($res_usu)>0)//($res_usu->fields>0)
                  {
                    $carpeta=$_ENV['APP_ENV'];//basename(getcwd());
                    if($carpeta=="prod")//"ws_app")
                    {
                      $url="http://10.1.1.4/consuerteinventarios/datos.php";
                    }
                    else//es pruebas
                    {
                      $url=$this->ip."/consuertepruebas/datos.php";
                    }
                    $arr_llano_ws=array();
                    if($tercero=="246")//edesa
                    {
                      $valorre="0";// en ceros para que tome el valor de la cuota normal
                    }
                    else if($tercero=="199")//congente
                    {
                      $iva=0;
                      $val_comi=$valorre*0.01;
                      $iva_comi=0;//number_format($val_comi, 0, '.', '')*$iva;//0.16;
                      $valorgi=$valorre-number_format($val_comi, 0, '.', '')-number_format($iva_comi, 0, '.', '');
                      $valorre="2|".$valorre."|".$val_comi."|".$valorre;
                    }
                    else if($tercero=="129")//llanogas ws
                    {
                      $this->log->logs("ID LLANOGAS  ".$facturaid);
                      array_push($arr_llano_ws,array("idfactura"=>$facturaid));
                      $sql_homo="select id_homologa from llanogas_regis_detalle_ws where id='".$facturaid."'";
                      $homol=$this->cnn->query('0', $sql_homo);//app $cnn->Execute($sql_homo);
                      if($homol)
                      {
                        if(count($homol)>0)//($homol->fields>0)
                        {
                          $id_homo=$homol[0]['id_homologa'];//$homol->fields[0];
                          if($id_homo!="" && $id_homo!="0" && $id_homo!="null")
                          {
                            array_push($arr_llano_ws,array("idfactura"=>$id_homo));
                          }
                        }
                      }
                    }
                    $extras="?con=".$con."&ws_app=1&nickname=".$nickname_cliente."&usuario=".$nickname_cliente."&tercero=".$tercero."&factura=".$factura."&especial=".$especial."&facturaid=".$facturaid."&valorre=".(int)$valorre."&arr_llano_ws=".json_encode($arr_llano_ws)."&valor_redondeado=".(int)$valor_redondeado;
                    $this->log->logs("url_pago ".$url.$extras);
                    $response = $this->http_client->request('GET', $url.$extras);//'https://api.github.com/repos/symfony/symfony-docs');

                    $statusCode = $response->getStatusCode();
                    $valor=null;
                    $cliente = null;
                    if($statusCode == "200")
                    {
                      $contentType = $response->getHeaders()['content-type'][0];
                      $valor = $response->getContent();
                      $this->log->logs("Resultado Recaudo SGC : ".$valor);
                      $dat_ws=explode("|",$valor);
                      if($tercero=="8")//si es emsa
                      {
                        $dato="Emsa";
                        if($dat_ws[0]==1)//pagado correctamente
                        {
                          $status=$dat_ws[0];
                          $id_reg_pago=$dat_ws[1];
                          $sql_pago="select distinct r.id_deta_factura,t.nombre, r.pin,r.fecha_recaudo,d.id_factura,
                          r.valor_recaudado, c.prs_documento
                          from emsa_recaudado r, emsa_regis_detalle d, territorios t, contratosventa c
                          where r.id='".$id_reg_pago."' and  r.id_deta_factura=d.id and r.punto_venta=t.codigo and r.usuario=c.login";

                          $r_p=$this->cnn->query('0', $sql_pago);//sgc $cnn->Execute($sql_pago);
                          if($r_p)
                          {
                            if(count($r_p)>0)//($r_p->fields>0)
                            {
                              $id_regis= $r_p[0]['id_deta_factura'];//$r_p->fields[0];
                              $fechar=$r_p[0]['fecha_recaudo'];//$r_p->fields[3];
                              $pin=$r_p[0]['pin'];//$r_p->fields[2];
                              $cliente=$r_p[0]['id_factura'];//$r_p->fields[4];
                              $total=number_format($r_p[0]['valor_recaudado'],0,'.',',');

                              $inser_app="INSERT INTO app_pagos_recaudos (id_tercero,usuario,id_regis_deta,id_recaudo,valor_re,fecha_r,imei)
                              VALUES('".$tercero."','". $nickname."','".$id_regis."','". $id_reg_pago."','".$r_p[0]['valor_recaudado']."','".$fechar."','".$imei."');";
                              $this->cnn->query('19', $inser_app);//app

                              $sq_hist="INSERT INTO hist_transacciones(tipo,descripcion,valor,usuario,id_transac,fecha_transa,status_nequi,datos)
                              values('5','Pago Servicios Publicos','-".$r_p[0]['valor_recaudado']."','".$nickname."','".$id_regis."','now()','0','".$dato."');";
                              $this->cnn->query('19', $sq_hist);//app $cnn_app->Execute($inser_app);
                            }
                          }
                          array_push($val, array("status" =>$status,"id_pago"=>$id_regis,"fechar"=>$fechar,"pin"=>$pin,"cliente"=>$cliente,"total"=>$total));
                        }
                        else
                        {
                          $status=$dat_ws[0];

                          if($status==-4)//
                          {
                            $msm="Se intento realizar un pago por un valor diferente al registrado en EMSA S.A";
                          }
                          else if($status==-1)
                          {
                            $msm="No esta habilitado o No existe el Cliente.";
                          }
                          else if($status==-3)
                          {
                            $msm="Hubo un error en la respuesta de EMSA S.A , Intente despues.";
                          }
                          else if($status==-2)
                          {
                            $msm="No se puede recaudar un valor en Ceros ($0)";
                          }
                          else if($status==-7)
                          {
                            $msm="Esta factura no tiene privilegios para pagar Vencida.";
                          }
                          else if($status==-8)
                          {
                            $msm="Error EMSA. Tiene varios Pagos con el mismo ID_TRANSACCION, Comuniquese con el administrador. ".$dat_ws[1];
                          }
                          else if($status==-9)
                          {
                            $msm="Error EMSA: Ya hay un pago en EMSA con el mismo valor, cliente y fecha. ".$dat_ws[1];
                          }
                          else if($status==10)//excepciion desconocida
                          {
                            $msm=$dat_ws[1];
                          }
                          else if($status==12)//excepciion desconocida
                          {
                            $msm="Error EMSA. Presento Fallo al momento de recaudar, Intente Nuevamente.".$dat_ws[1];
                          }
                          else
                          {
                            $msm=$dat_ws[1];
                            if(empty($dat_ws[1])) $msm="Error sin identificar , contacte al administrador del sistema.";
                          }
                          array_push($val, array("status" =>0,"msm"=>$msm));
                        }
                      }
                      else if($tercero=="129")//llanogas
                      {
                        $dato="Llanogas ws";
                        if($dat_ws[0]==1)//pagado correctamente
                        {
                          $status=$dat_ws[0];
                          $id_reg_pago=$dat_ws[1];
                          $id_pago_pago_bio=$dat_ws[2];
                          $id_homologo=0;
                          //$pagados=array();
                          $pagados="";
                          if( $id_reg_pago!="0" && $id_pago_pago_bio!="0")//hubo  llanogas y bio
                          {
                            $pagados.="'".$id_reg_pago."','".$id_pago_pago_bio."'";
                            $id_homologo=$id_pago_pago_bio;
                          }
                          else if( $id_reg_pago=="0" && $id_pago_pago_bio!="0")//hubo  solo bioagricola
                          {
                            $pagados.="'".$id_pago_pago_bio."'";
                          }
                          else if($id_reg_pago!="0" && $id_pago_pago_bio=="0")
                          {
                            $pagados.="'".$id_reg_pago."'";
                          }
                          /*$sql_pago="select distinct r.id_deta_factura,t.nombre, r.pin,r.fecha_recaudo,substr(d.factura,41), d.valor_serviciop, c.prs_documento, d.id_usuario,d.valor_servicioa  from llanogas_recaudado r, llanogas_regis_detalle d, territorios t, contratosventa c
                          where r.id='".$id_reg_pago."' and  r.id_deta_factura=d.id and r.punto_venta=t.codigo and r.usuario=c.login";
                          */
                          /*$sql_pago="select distinct r.id_deta_factura,t.nombre, r.pin,r.fecha_recaudo,d.factura, d.valor_serviciop, c.prs_documento, d.usuario,d.tipo_convenio,d.id_homologa,(select CASE when valor_serviciop is null then 0 else valor_serviciop  end from llanogas_regis_detalle_ws where id=d.id_homologa and estado='C') as val_homologa
                          from llanogas_recaudado_ws r, llanogas_regis_detalle_ws d, territorios t, contratosventa c
                          where r.id='".$id_reg_pago."' and  r.id_deta_factura=d.id
                          and r.punto_venta=t.codigo and r.usuario=c.login and r.estado='0'";*/
                          $sql_pago=" SELECT distinct r.id_deta_factura,t.nombre, r.pin,r.fecha_recaudo,d.factura, d.valor_serviciop,
                          c.prs_documento, d.usuario,d.tipo_convenio,d.id_homologa,z.valor_serviciop as valor_serviciop2,z.factura,r.id
                          from llanogas_recaudado_ws r , llanogas_regis_detalle_ws d LEFT JOIN  llanogas_regis_detalle_ws z ON z.id=d.id_homologa ,
                          territorios t, contratosventa c
                          where r.id in (".$pagados.")
                          and  r.id_deta_factura=d.id
                          and r.punto_venta=t.codigo
                          and r.usuario=c.login
                          and r.estado='0'";

                          $r_p=$this->cnn->query('0', $sql_pago);//sgc $cnn->Execute($sql_pago);
                          if($r_p)
                          {
                            $val_rec=0;
                            $total_g=0;
                            $name_ref="";
                            foreach ($r_p as $key => $row)
                            {
                              $tipo_co=$row['tipo_convenio'];//$row[8];//322 llano 317 bio
                              if($tipo_co=="322")
                              {
                                $val_rec=$row['valor_serviciop'];//$row[5];//valor recibo
                                $id_reg_pago=$row['id'];//$row[12];//valor recibo
                                $name_ref="Llanogas : ".$row['factura']."";
                                $id_regis=$row['id_deta_factura'];//$row[0];
                                $pin=$row['pin'];//row[2];
                                $fechar=$row['fecha_recaudo'];//$row[3];
                              }
                              else
                              {
                                $id_reg_pago=$row['id'];//$row[12];//valor recibo
                                $id_regis=$row['id_deta_factura'];//$row[0];
                                $val_rec=$row['valor_serviciop'];//$row[5];//valor recibo
                                //$name_ref="BioAgricola: ".$row[4]."";
                                $name_ref="BioAgricola: ".$row['factura']."";
                                $pin=$row['pin'];//$row[2];
                                $fechar=$row['fecha_recaudo'];//$row[3];
                              }
                              $cliente.=$name_ref."\n";
                              $total_g+=(int)$val_rec;
                            } //fin foreach

                            $total=number_format($total_g,0,'.',',');
                            $inser_app="INSERT INTO app_pagos_recaudos (id_tercero,usuario,id_regis_deta,id_recaudo,valor_re,fecha_r,imei,extras)
                            VALUES('".$tercero."','". $nickname."','".$id_regis."','".$id_reg_pago."','".$total_g."','".$fechar."','".$imei."','".$cliente."');";
                            $this->cnn->query('19', $inser_app);//app

                            $sq_hist="INSERT INTO hist_transacciones(tipo,descripcion,valor,usuario,id_transac,fecha_transa,status_nequi,datos)
                            values('5','Pago Servicios Publicos','-".$total_g."','".$nickname."','".$id_regis."','now()','0','".$dato."');";
                            $this->cnn->query('19', $sq_hist);//app $cnn_app->Execute($inser_app);

                            array_push($val, array("status" =>$status,"id_pago"=>$id_regis,"fechar"=>$fechar,"pin"=>$pin,"cliente"=>$cliente,"total"=>$total));
                          }
                        }
                        else
                        {
                          $rr="Ups!! ";
                          if($dat_ws[0]==4)
                          {
                            $rr.=" No tiene un PDV asignado. favor Validar.";
                          }
                          else if($dat_ws[0]==5)
                          {
                            $rr.=" Hora Limite Sobrepasada.";
                          }
                          else if($dat_ws[0]==999)
                          {
                            $rr.=$dat_ws[1];//" La Recarga supera el monto permitido, su saldo es $".number_format((int)($dat_ws[1]),0,'',',');
                          }
                          else
                          {
                            $rr.=$dat_ws[1];
                            if(empty($dat_ws[1])) $rr.="Error sin identificar , contacte al administrador del sistema.";
                          }
                          array_push($val, array("status" =>0,"msm"=>$rr));
                        }
                      }
                      else if($tercero=="128")//acueducto
                      {
                        $dato="Acueducto Villavicencio";
                        if($dat_ws[0]==1)//pagado correctamente
                        {
                          $status=$dat_ws[0];
                          $id_reg_pago=$dat_ws[1];

                          $sql_pago="select distinct r.id_deta_factura,t.nombre, r.pin,r.fecha_recaudo,d.factura, d.valor_serviciop, c.prs_documento
                          from acueducto_recaudado r, acueducto_regis_detalle d, territorios t, contratosventa c
                          where r.id='".$id_reg_pago."' and  r.id_deta_factura=d.id and r.punto_venta=t.codigo and r.usuario=c.login";

                          //$cnn= new conn(0,1);//conexion sgc
                          $r_p=$this->cnn->query('0', $sql_pago);//sgc $cnn->Execute($sql_pago);
                          if($r_p)
                          {
                            if(count($r_p)>0)//($r_p->fields>0)
                            {
                              $id_regis= $r_p[0]['id_deta_factura'];//$r_p->fields[0];
                              $fechar=$r_p[0]['fecha_recaudo'];//$r_p->fields[3];
                              $pin=$r_p[0]['pin'];//$r_p->fields[2];
                              $cliente=(int)$r_p[0]['factura'];//$r_p->fields[4];
                              //$total=number_format($r_p->fields[5],0,'.',',');
                              $total=number_format($r_p[0]['valor_serviciop'],0,'.',',');

                              $inser_app="INSERT INTO app_pagos_recaudos (id_tercero,usuario,id_regis_deta,id_recaudo,valor_re,fecha_r,imei)
                              VALUES('".$tercero."','". $nickname."','".$id_regis."','". $id_reg_pago."','".$r_p[0]['valor_serviciop']."','".$fechar."','".$imei."');";
                              $this->cnn->query('19', $inser_app);//app

                              $sq_hist="INSERT INTO hist_transacciones(tipo,descripcion,valor,usuario,id_transac,fecha_transa,status_nequi,datos)
                              values('5','Pago Servicios Publicos','-".$r_p[0]['valor_serviciop']."','".$nickname."','".$id_regis."','now()','0','".$dato."');";
                              $this->cnn->query('19', $sq_hist);//app $cnn_app->Execute($inser_app);
                            }
                          }
                          array_push($val, array("status" =>$status,"id_pago"=>$id_regis,"fechar"=>$fechar,"pin"=>$pin,"cliente"=>$cliente,"total"=>$total));
                        }
                        else
                        {
                          $rr="Ups!! ";
                          if($dat_ws[0]==4)
                          {
                            $rr.=" No tiene un PDV asignado. favor Validar.";
                          }
                          else if($dat_ws[0]==5)
                          {
                            $rr.=" Hora Limite Sobrepasada.";
                          }
                          else if($dat_ws[0]==999)
                          {
                            $rr.=$dat_ws[1];//" La Recarga supera el monto permitido, su saldo es $".number_format((int)($dat_ws[1]),0,'',',');
                          }
                          else
                          {
                            $rr.=$dat_ws[1];
                            if(empty($dat_ws[1])) $rr.="Error sin identificar , contacte al administrador del sistema.";
                          }
                          array_push($val, array("status" =>0,"msm"=>$rr));
                        }
                      }
                      else if($tercero=="246")//EDESA
                      {
                        $dato="Edesa";
                        if($dat_ws[0]==1)//pagado correctamente
                        {
                          $status=$dat_ws[0];
                          $id_reg_pago=$dat_ws[1];

                          $sql_pago="select distinct r.id_deta_factura,t.nombre, r.pin,r.fecha_recaudo,d.factura, r.valor_recaudado, c.prs_documento,d.id_usuario
                          from edesa_recaudado r, edesa_regis_detalle d, territorios t, contratosventa c
                          where r.id='".$id_reg_pago."' and  r.id_deta_factura=d.id and r.punto_venta=t.codigo and r.usuario=c.login";
                          $r_p=$this->cnn->query('0', $sql_pago);//sgc $cnn->Execute($sql_pago);
                          if($r_p)
                          {
                            if(count($r_p)>0)//($r_p->fields>0)
                            {
                              $id_regis= $r_p[0]['id_deta_factura'];//$r_p->fields[0];
                              $fechar=$r_p[0]['fecha_recaudo'];//$r_p->fields[3];
                              $pin=$r_p[0]['pin'];//$r_p->fields[2];
                              $cliente=(int)$r_p[0]['factura'];//$r_p->fields[4];
                              $total=number_format($r_p[0]['valor_recaudado'],0,'.',',');

                              $inser_app="INSERT INTO app_pagos_recaudos (id_tercero,usuario,id_regis_deta,id_recaudo,valor_re,fecha_r,imei)
                              VALUES('".$tercero."','". $nickname."','".$id_regis."','". $id_reg_pago."','".$r_p[0]['valor_recaudado']."','".$fechar."','".$imei."');";
                              $this->cnn->query('19', $inser_app);//app

                              $sq_hist="INSERT INTO hist_transacciones(tipo,descripcion,valor,usuario,id_transac,fecha_transa,status_nequi,datos)
                              values('5','Pago Servicios Publicos','-".$r_p[0]['valor_recaudado']."','".$nickname."','".$id_regis."','now()','0','".$dato."');";
                              $this->cnn->query('19', $sq_hist);//app $cnn_app->Execute($inser_app);
                            }
                          }
                          array_push($val, array("status" =>$status,"id_pago"=>$id_regis,"fechar"=>$fechar,"pin"=>$pin,"cliente"=>$cliente,"total"=>$total));
                        }
                        else
                        {
                          $rr="Ups!! ".$dat_ws[0];
                          if($dat_ws[0]==4)
                          {
                            $rr.=" No tiene un PDV asignado. favor Validar.";
                          }
                          else if($dat_ws[0]==5)
                          {
                            $rr.=" Hora Limite Sobrepasada.";
                          }
                          else if($dat_ws[0]==999)
                          {
                            $rr.=$dat_ws[1];//" Hora Limite Sobrepasada.";
                          }
                          else
                          {
                            $rr.=$dat_ws[1];
                            if(empty($dat_ws[1])) $rr.="Error sin identificar , contacte al administrador del sistema.";
                          }
                          array_push($val, array("status" =>0,"msm"=>$rr));
                        }
                      }
                      else if($tercero=="199")//congente
                      {
                        $dato="Congente";
                        if($dat_ws[0]==1)//pagado correctamente
                        {
                          $status=$dat_ws[0];
                          $id_reg_pago=$dat_ws[1];

                          $sql_pago="select distinct r.id_deta_factura,t.nombre, r.pin,r.fecha_recaudo,d.cedula, r.valor_recaudado, c.prs_documento, d.nombres, r.prefijo_factura, r.factura_nro,
                          r.valor_comision,r.valor_neto,r.iva, d.direccion,d.telefono from congente_recaudado r, congente_regis_detalle d, territorios t, contratosventa c
                          where r.id='".$id_reg_pago."' and  r.id_deta_factura=d.id and r.punto_venta=t.codigo and r.usuario=c.login ";

                          //$cnn= new conn(0,1);//conexion sgc
                          $r_p=$this->cnn->query('0', $sql_pago);//sgc $cnn->Execute($sql_pago);
                          if($r_p)
                          {
                            if(count($r_p)>0)//($r_p->fields>0)
                            {
                              $id_regis=$r_p[0]['id_deta_factura'];// $r_p->fields[0];
                              $fechar=$r_p[0]['fecha_recaudo'];//$r_p->fields[3];
                              $pin=$r_p[0]['pin'];//$r_p->fields[2];
                              $cliente=(int)$r_p[0]['cedula'];//$r_p->fields[4];
                              //$total=number_format($r_p->fields[5],0,'.',',');
                              $total=number_format($r_p[0]['valor_recaudado'],0,'.',',');

                              $inser_app="INSERT INTO app_pagos_recaudos (id_tercero,usuario,id_regis_deta,id_recaudo,valor_re,fecha_r,imei)
                              VALUES('".$tercero."','". $nickname."','".$id_regis."','". $id_reg_pago."','".$r_p[0]['valor_recaudado']."','".$fechar."','".$imei."');";
                              $this->cnn->query('19', $inser_app);//app

                              $sq_hist="INSERT INTO hist_transacciones(tipo,descripcion,valor,usuario,id_transac,fecha_transa,status_nequi,datos)
                              values('5','Pago Servicios Publicos','-".$r_p[0]['valor_recaudado']."','".$nickname."','".$id_regis."','now()','0','".$dato."');";
                              $this->cnn->query('19', $sq_hist);//app $cnn_app->Execute($inser_app);
                            }
                          }
                          array_push($val, array("status" =>$status,"id_pago"=>$id_regis,"fechar"=>$fechar,"pin"=>$pin,"cliente"=>$cliente,"total"=>$total));
                        }
                        else
                        {
                          $rr="Ups!! ".$dat_ws[0];
                          if($dat_ws[0]==4)
                          {
                            $rr.=" No tiene un PDV asignado. favor Validar.";
                          }
                          else if($dat_ws[0]==5)
                          {
                            $rr.=" Hora Limite Sobrepasada.";
                          }
                          else if($dat_ws[0]==999)
                          {
                            $rr.=$dat_ws[1];//" La Recarga supera el monto permitido, su saldo es $".number_format((int)($dat_ws[1]),0,'',',');
                          }
                          else
                          {
                            $rr.=$dat_ws[1];
                            if(empty($dat_ws[1])) $rr.="Error sin identificar , contacte al administrador del sistema.";
                          }
                          array_push($val, array("status" =>0,"msm"=>$rr));
                        }
                      }
                    }
                    else
                    {
                      $msm="Error Conexion Consulta: ".$response->getContent();//$e->getMessage();
                      $this->log->logs($msm,$response->getInfo('debug'));
                      array_push($val, array("status" =>0,"msm"=>$msm,"estado"=>"V"));
                    }//fin llamada res recaudo
                  }
                  else
                  {
                    array_push($val, array("status" =>0,"msm"=>"lO SENTIMOS, su usuario no esta activo o no Existe."));
                  }
                }
                if($bandera==999)//permite consultar y pagar
                {
                  array_push($val, array("status" =>0,"msm"=>"LO SENTIMOS, LA PLATAFORMA ESTA INACTIVA3"));
                }
                //submit=&con=9.3&usuario="+usuario+"&tercero="+tercero+"&factura="+factura+"&facturaid="+facturaid+"&estado1="+estado1+"&valorre="+valorre+"&especial="+0,
              }
              else
              {
                array_push($val, array("status" =>0,"msm"=>$info[1]));
              }
              $this->log->logs("RESPUESTA: ", array($val));
              $this->log->logs("*******FIN PROCESO PAGAR CONVENIOS*******");
            }
            else if($con=="5")// retorna el listados de los operadores activos con sus paquetes funciona
            {
              $this->log->logs("*******INICIA LISTA OPERADORES BEMOVIL*******");
              $datos=explode("|", $array);
              $tipouser=$datos[0];//tipo usuario 1=cliente 2=vendedora sgc 5= kiosco_sgc

              $control_usuario_tipo=0;
              if($tipouser=="6")
              {
                if(!$this->doAuthenticate())
                {
                  array_push($val, array("status" =>0,'msm' => "Invalido Usuario o Password Webservice"));
                  $control_usuario_tipo=1;
                }
                else
                {
                  $control_usuario_tipo=0;
                }
              }

              if($control_usuario_tipo==0)
              {
                $sql="SELECT p.id as id_producto,p.id_paq,p.id_ope,p.descripcion,p.valor,o.descripcion as des_ope,p.tipo_paque,t.descripcion as tipo_pro,o.tipo as tipo
                from bemovil_tipo_operador o, bemovil_paquetes p,bemovil_tipo_paquete t
                where  o.codigo=p.id_ope
                and t.id=p.tipo_paque
                and cast(o.fecha_ini as date)<='".date('Y-m-d')."' and (cast(o.fecha_fin as date)>='".date('Y-m-d')."' or o.fecha_fin is null) and p.estado='0'
                union all
                select v.id as id_producto,v.id ,v.id as ope,'Runt' as des_ope,v.valor,v.descripcion,v.id  as ope,'0' as des,2 as tipo
                from bemovil_hvehicular v
                where cast(v.fechai as date)<='".date('Y-m-d')."' and (cast(v.fechaf as date)>='".date('Y-m-d')."' or v.fechaf is null)
                union all
                select v.id as id_producto,v.cod_paq as id_paq,v.id_ope as ope,'Bemovil Pines' as des_ope,v.valor,v.concepto,v.id  as id_producto,'0' as des,3 as tipo
                from bemovil_pines v
                where cast(v.fechai as date)<='".date('Y-m-d')."' and (cast(v.fechaf as date)>='".date('Y-m-d')."' or v.fechaf is null) and v.estado='0'
                order by tipo,id_ope";
                try
                {
                  $r=$this->cnn->query('0', $sql);//sgc $cnn->_Execute($sql);
                  if($r)
                  {
                    if(count($r)>0)//($r->fields>0)
                    {
                      $rec=array();
                      $paq=array();
                      $pin=array();
                      $run=array();
                      $cont=0;
                      foreach ($r as $key => $row)
                      {
                        $vista_valor=number_format($row['valor'],0,'',',');
                        if($row['tipo']=="0")//($r->fields[8]=="0")//son recargas
                        {
                          array_push($rec, array("id_producto"=>$row['id_producto'],"id_paq"=>$row['id_paq'],"id_ope"=>$row['id_ope'],"descripcion"=>mb_convert_encoding($row['descripcion'],'utf8'),"valor"=>(int)$row['valor'],"desc_ope"=>mb_convert_encoding($row['des_ope'],'utf8'),"tipo_paq"=>$row['tipo_paque'],"tipo_pro"=>$row['tipo_pro'],"tipo"=>$row['tipo'],"vista_total"=>"$ ".$vista_valor));
                        }
                        else if($row['tipo']=="1")//($r->fields[8]=="1")//son pquetes
                        {
                          array_push($paq, array("id_producto"=>$row['id_producto'],"id_paq"=>$row['id_paq'],"id_ope"=>$row['id_ope'],"descripcion"=>mb_convert_encoding($row['descripcion'],'utf8'),"valor"=>(int)$row['valor'],"desc_ope"=>mb_convert_encoding($row['des_ope'],'utf8'),"tipo_paq"=>$row['tipo_paque'],"tipo_pro"=>$row['tipo_pro'],"tipo"=>$row['tipo'],"vista_total"=>"$ ".$vista_valor));

                        }
                        else if($row['tipo']=="2")//($r->fields[8]=="2")//son pquetes
                        {
                          array_push($run, array("id_producto"=>$row['id_producto'],"id_paq"=>$row['id_paq'],"id_ope"=>$row['id_ope'],"descripcion"=>mb_convert_encoding($row['descripcion'],'utf8'),"valor"=>(int)$row['valor'],"desc_ope"=>mb_convert_encoding($row['des_ope'],'utf8'),"tipo_paq"=>$row['tipo_paque'],"tipo_pro"=>$row['tipo_pro'],"tipo"=>$row['tipo'],"vista_total"=>"$ ".$vista_valor));
                        }
                        else if($row['tipo']=="3")//($r->fields[8]=="3")//son pquetes
                        {
                          array_push($pin, array("id_producto"=>$row['id_producto'],"id_paq"=>$row['id_paq'],"id_ope"=>$row['id_ope'],"descripcion"=>mb_convert_encoding($row['descripcion'],'utf8'),"valor"=>(int)$row['valor'],"desc_ope"=>mb_convert_encoding($row['des_ope'],'utf8'),"tipo_paq"=>$row['tipo_paque'],"tipo_pro"=>$row['tipo_pro'],"tipo"=>$row['tipo'],"vista_total"=>"$ ".$vista_valor));
                        }
                        $cont++;
                      }
                      array_push($val, array("status" =>1,"recargas"=>$rec,"paquetes"=>$paq,"pines"=>$pin,"runt"=>$run));
                    }
                    else
                    {
                      array_push($val, array("status" =>0,"msm"=>"No Se encontraron Operadores de recargas."));
                    }
                  }
                  else
                  {
                    array_push($val, array("status" =>0,"msm"=>"No Se encontraron Operadores de recargas."));
                  }
                }
                catch(\Exception $e)
                {
                  array_push($val, array("status" =>0,"msm"=>"Ocurrio un error.".$e));
                }
              }
              $this->log->logs("*******FIN LISTA OPERADORES BEMOVIL*******");
            }
            else if($con=="6")//recargas bemovil
            {
              $this->log->logs("*******INICIA PROCESO RECARGA BEMOVIL*******");

              $datos=explode("|", $array);
              $nickname=$datos[0];//nickanme vendedora o usuario
              $id_ope=$datos[1];//siempre debe ser emsa
              $total=$datos[2];//total
              $telefo=$datos[3];//telefono o correo dependiendo del servicio
              $id_paq=$datos[4];
              $id_pro=$datos[5];
              $tipo_r=$datos[6];
              $tipou=$datos[7];
              $placa=$datos[8];//para runt y pines
              $imei=$datos[9];//imei
              $tele=null;
              $correo=null;
              $infoFE=null;
              $tip_doc = null;
              $ced_cli = null;
              $nom_cli = null;
              $ape_cli = null;
              $cor_cli = null;
              $reg_cli = null;
              $dig_cli = null;

              $countDatos = count($datos);
              if($countDatos > 10 && $datos[10] != null && $datos[10] != "null")
              {
                $infoFE=$datos[10];
                $infoFE = trim($infoFE, "[]");
                $this->log->logs("infoFE $infoFE");
                $elementos = explode(",", $infoFE);
                $elementos = array_map('trim', $elementos);

                $tip_doc = $elementos[5];
                $ced_cli = $elementos[0];
                $nom_cli = $elementos[1];
                $ape_cli = $elementos[2];
                $cor_cli = $elementos[4];
                $reg_cli = $elementos[6];
                $dig_cli = $elementos[7];
              }

              $ws_app="1";
              $carpeta=$_ENV['APP_ENV'];//basename(getcwd());
              //echo $carpeta;
              $nickname_cliente=$nickname;
              $bandera=0;
              $info=null;
              $fecha_act = date("Y-m-d H:i:s");
              if($carpeta=="prod")//"ws_app")
              {
                $url="http://10.1.1.4/consuerteinventarios/bemovil_ws.php";
              }
              else//es pruebas
              {
                // $url="http://172.31.28.103/consuertepruebas/bemovil_ws.php";
                $url=$this->ip."/consuertepruebas/bemovil_ws.php";
              }
              $validacion=1;
              $control_saldo=0;
              //$cnn= new conn(0,1);//conexion sgc
              if($tipou<"5")
              {
                if($c_v<0)//si fecha actual es menor a la fecha de control de inicio sistema viejo
                {
                  $resultados=$this->analizar_bloqueo($nickname,$total);

                  $info=explode("|",$resultados);
                  $vta_actual=$info[7];
                  $tope=$info[8];
                  $vta_calc=$info[9];

                  if($info[0]==1)
                  {
                    if((int)$vta_actual<=(int)$tope)//si total(recaudado+transaccion) es menor o igual al tope asignado al usuario
                    {
                      $control_saldo=1;
                    }
                    else
                    {
                      $validacion=0;
                      $control_saldo=0;
                      //array_push($val, array("status" =>0,"msm"=>"6.La Recarga supera el monto permitido, su saldo es $".number_format((int)($tope-$vta_calc),0,'',',')));
                    }
                  }
                }
                else//inicia sistema nuevo
                {
                  // $info[0]=1;
                  $control_saldo=1;
                  $res_val_usu = $this->validar_estado_usuario($nickname);
                  $info[0] = $res_val_usu["code"];
                  $info[1] = $res_val_usu["message"];
                }//finaliza sistema nuevo
              }
              else
              {
                $info[0]=1;
                $control_saldo=1;
              }

              if($info[0]==1)
              {
                if($control_saldo==1)
                {
                  if ($tipo_r=="0" || $tipo_r=="1")
                  {//Recargas y paquetes
                    $sql1="select r.id,r.fecha_sys from bemovil_recargas_recaudado r, (SELECT MAX(fecha_sys) from bemovil_recargas_recaudado
                    where tel='$telefo' and valor='$total' and usuario='$nickname' and estado='0' and id_operador='$id_ope') f
                    where f.max=r.fecha_sys and r.tel='$telefo' and r.valor='$total' and r.usuario='$nickname' and r.estado='0' and r.id_operador='$id_ope'";
                  }
                  else if($tipo_r=="2")//runt
                  {
                    $sql1="select r.id,r.fecha_sys from bemovil_hvehicular_recaudado r, (SELECT MAX(fecha_sys) from bemovil_hvehicular_recaudado
                    where dest_correo='$telefo' and valor='$total' and usuario='$nickname' and estado='0' and placa='$placa') f
                    where f.max=r.fecha_sys and r.dest_correo='$telefo' and r.valor='$total' and r.usuario='$nickname' and r.estado='0' and r.placa='$placa'";
                  }
                  else if($tipo_r=="3")//Pines
                  {
                    $sql1="select r.id,r.fecha_sys from bemovil_pines_recaudado r, (SELECT MAX(fecha_sys) from bemovil_pines_recaudado
                    where correo='$telefo' and valor='$total' and usuario='$nickname' and estado='0' and id_pin='$id_pro') f
                    where f.max=r.fecha_sys and r.correo='$telefo' and r.valor='$total' and r.usuario='$nickname' and r.estado='0' and r.id_pin='$id_pro'";
                  }
                  $st=$this->cnn->query('0', $sql1);//sgc $cnn->Execute($sql1);
                  if($st)
                  {
                    if(count($st)>0)//($st->fields>0)
                    {
                      $id_trans=$st[0]['id'];//$st->fields[0];
                      //$fecha_trans_aux=explode(".",$st->fields[1]);
                      $fecha_trans_aux=explode(".",$st[0]['fecha_sys']);//$st->fields[1]);
                      $fecha_trans=$fecha_trans_aux[0];
                      $segundos = strtotime($fecha_act) - strtotime($fecha_trans);
                      $minutos=5-($segundos/60);
                      if($segundos<300)
                      {
                        if(number_format($minutos, 0)==0)
                        {
                          $minutos=1;
                        }
                        //array_push($val, array("status" =>0,"msm"=>"No es posible hacer esa Transaccion, ud ya realizo una Transaccion al numero: $telefo por un valor de $total, espere ".number_format($minutos, 0)." minutos"));
                        array_push($val, array("status" =>0,"msm"=>"Ya se realizo una transaccion con los mismos datos, espere ".(number_format($minutos, 0)-1)." minutos ".(60-($segundos%60))." segundos"));
                        $validacion=0;
                      }
                    }
                  }
                  else
                  {
                    $validacion=1;//$validacion=0;
                    //array_push($val, array("status" =>0,"msm"=>"Error de ejecucion SQL, Intente nuevamente"));
                  }
                }
                else
                {
                  $validacion=0;
                  array_push($val, array("status" =>0,"msm"=>"6.La Recarga supera el monto permitido, su saldo es $".number_format((int)($tope-$vta_calc),0,'',',')));
                }
              }
              else
              {
                $validacion=0;
                array_push($val, array("status" =>0,"msm"=>$info[1]));
              }
              //$validacion=9;

              if ($validacion==1)
              {

                /*if($nickname!="CV1121818779" )//bloquea usuario
                {*/
                if($tipou=="2" || $tipou=="6")//si es nivel vendedora
                {
                  /* $cnn= new conn(0,1);//conexion sgc
                  $cnn2= new conn(2,2);//conexion gamble */
                  $sql1="SELECT  distinct c.hraprs_ubcneg_trtrio_codigo as punto,c.ubcntrtrio_codigo_compuesto_de as ccostos,c.ubcntrtrio_codigo_compuesto__1 as zona
                  from controlhorariopersonas c,contratosventa c2,usuarios c3
                  where (c.login,c.cal_dia,c.hhentrada) in( select c4.login,c4.cal_dia,max(c4.hhentrada) as hhentrada from controlhorariopersonas c4,
                  (select c3.login, max(c3.cal_dia) as cal_dia from controlhorariopersonas c3 where c3.login='".$nickname."'  group by c3.login ) v
                  where c4.login=v.login
                  and  c4.cal_dia=v.cal_dia
                  group by c4.login,c4.cal_dia) and  c2.login=c.login and c2.fechafinal is null and c3.loginusr=c.login and c3.estado='A'";

                  $st=$this->cnn->query('2', $sql1);//gamble $cnn2->Execute($sql1);
                  if($st)
                  {
                    if(count($st)>0)//($st->fields>0)
                    {
                      //usuario habilitado
                      $pdv_r=$st[0]['PUNTO'];//$st->fields[0];
                      $sql_autoriza="select id_tercero from emsa_asignacion_punto
                      where punto_venta='".$pdv_r."' and id_tercero='480' and estado='A' ";
                      $res_autoriza=$this->cnn->query('0', $sql_autoriza);//sgc $cnn->Execute($sql_autoriza);
                      if($res_autoriza)
                      {
                        if(count($res_autoriza)>0)//($res_autoriza->fields>0)
                        {
                          $bandera=0;
                        }
                        else
                        {
                          $bandera=1;
                          array_push($val, array("status" =>0,"msm"=>"4.Su Punto de venta no esta autorizado para BEMOVIL."));
                        }
                      }
                      else
                      {
                        $bandera=1;
                        array_push($val, array("status" =>0,"msm"=>"3.No se pudo validar la autorizacion Intenta nuevamente."));
                      }
                    }
                    else
                    {
                      $bandera=1;
                      array_push($val, array("status" =>0,"msm"=>"1.Debe revisar su contrato o esta bloqueado.Contacte a soporte"));
                    }
                  }
                  else
                  {
                    $bandera=1;
                    array_push($val, array("status" =>0,"msm"=>"2.Debe revisar su contrato o esta bloqueado.Contacte a soporte"));
                  }

                  if($bandera==0)
                  {
                    //$cnn_app= new conn(0,0);//conexion bd app
                    $sql="select u.id from users u where u.mail='".$nickname."' and u.estado='A'";
                    $res_usu=$this->cnn->query('19', $sql);//app $cnn_app->Execute($sql);
                    if(count($res_usu)>0)//($res_usu->fields>0)
                    {
                      $infoUrlFE = "&tip_doc=$tip_doc&ced_cli=$ced_cli&nom_cli=$nom_cli&ape_cli=$ape_cli&cor_cli=$cor_cli&reg_cli=$reg_cli&dig_cli=$dig_cli";
                      $infoUrlFE = mb_convert_encoding($infoUrlFE, 'ISO-8859-1', 'UTF-8');
                      $vali=0;
                      if($tipo_r=="0")//recargas
                      {
                        $con=1;
                        $extras="?con=".$con."&ws_app=1&nickname=".$nickname."&ope=".$id_ope."&cel=".$telefo."&valor=".$total."&id_pro=".$id_pro;
                        if($con=="" || $nickname=="" || $id_ope=="" || $telefo=="" || $total=="" || $id_pro=="")
                        {
                          $vali=1;
                        }
                      }
                      else if($tipo_r=="1")//paquetes
                      {
                        $con=3;
                        $extras="?con=".$con."&ws_app=1&nickname=".$nickname."&ope=".$id_ope."&cel=".$telefo."&valor=".$total."&id_paquete=".$id_paq."&id_pro=".$id_pro;
                        if($con=="" || $nickname=="" || $id_ope=="" || $telefo=="" || $total=="" || $id_paq=="" || $id_pro=="")
                        {
                          $vali=1;
                        }
                      }
                      else if($tipo_r=="2")//runt
                      {
                        $con=4;
                        $extras="?con=".$con."&ws_app=1&nickname=".$nickname."&placa=".$placa."&valor=".$total."&id_pro=".$id_pro."&correo=".$telefo."&tele=".$tele;
                        if($con=="" || $nickname=="" || $placa=="" || $total=="" || $id_pro=="" || $telefo=="")
                        {
                          $vali=1;
                        }
                      }
                      else if($tipo_r=="3")//pines
                      {
                        $con=5;
                        //con=5&id_paq="+id_paq+"&valor="+valor+"&id_ope="+id_ope+"&correo="+correo+"&tele="+tele+"&id_pro="+id_pro,
                        $extras="?con=".$con."&ws_app=1&nickname=".$nickname."&id_paq=".$id_paq."&valor=".$total."&id_ope=".$id_ope."&correo=".$telefo."&tele=".$tele."&id_pro=".$id_pro;
                        if($con=="" || $nickname=="" || $id_paq=="" || $total=="" || $id_ope=="" || $telefo=="" || $id_pro=="")
                        {
                          $vali=1;
                        }
                      }
                      $this->log->logs("ws_bemovil sgc  = ".$url.$extras.$infoUrlFE);
                      if($vali==0)
                      {
                        $valor=null;
                        try
                        {
                          $response = $this->http_client->request('GET', $url.$extras.$infoUrlFE);
                          // $response = $this->http_client->request('GET', $url.$extras);
                          $statusCode = $response->getStatusCode();

                          if($statusCode == "200")
                          {
                            $contentType = $response->getHeaders()['content-type'][0];
                            $valor = $response->getContent();
                          }
                          else
                          {
                            $msm="Error Conexion Sgc: ".$response->getContent();//$e->getMessage();
                            $this->log->logs($msm,$response->getInfo('debug'));
                          }//fin llamada res sgc
                        }
                        catch (\Exception $e)
                        {
                          $this->log->logs("ERROR file_get_contents ".$e);
                        }
                        $valor = preg_replace('([^A-Za-z0-9 |])', '', $valor);
                        $this->log->logs("RESPUESTA SGC ".$valor."|".$telefo);

                        $valor=$this->validarTransaccionBeMovil($valor,$telefo,$total,$nickname,$fecha_act,$id_ope,$placa,$id_pro,$tipo_r);

                        $dat_ws=explode("|",$valor);
                        $this->log->logs("RESPUESTA SGC2 ".$valor."|".$telefo);
                        if($con==1  && $dat_ws[0]=="0")//respuesta de  recargas exitosa
                        {
                          //$cnn= new conn(0,1);//conexion sgc
                          $id_r=$dat_ws[1];
                          $sql_impri="select r.id,r.cant,r.fecha,r.hora,r.tel,r.valor,r.operador,r.usuario,r.pdv,t1.nombre,
                          o.descripcion as operador,p.descripcion as producto,t.descripcion as tipo_producto,r.pin
                          from bemovil_recargas_recaudado r,bemovil_tipo_paquete t,bemovil_paquetes p,bemovil_tipo_operador o ,territorios t1
                          where r.id='".$id_r."'
                          and r.id_operador=o.codigo
                          and p.tipo_paque=t.id
                          and p.id_paq=r.id_paq
                          and r.id_operador=p.id_ope
                          and ('".date("Y-m-d", strtotime("-1 day"))."'>=o.fecha_ini and
                            '".date("Y-m-d", strtotime("-1 day"))."'<=o.fecha_fin  or '".date("Y-m-d", strtotime("-1 day"))."'>=o.fecha_ini and o.fecha_fin is null)
                          and t1.codigo=r.pdv ";

                          $sql1="select nro_resolucion, fecha_resolucion, num_ini, num_fin, prefijo
                          from bemovil_resolucion_dian where fecha_ini<='".date('Y-m-d')."'
                          and (fecha_fin>='".date('Y-m-d')."' or fecha_fin is null)  ";

                          $res1=$this->cnn->query('0', $sql1);//sgc $cnn->Execute($sql1);
                          //echo $sql1;

                          if(count($res1)>0)//($res1->RecordCount()!=0)
                          {
                            $resu_dian=$res1[0]['nro_resolucion'];//$res1->fields[0];
                            $fecha_dian=$res1[0]['fecha_resolucion'];//$res1->fields[1];
                            $num_i=$res1[0]['num_ini'];//$res1->fields[2];
                            $num_f=$res1[0]['num_fin'];//$res1->fields[3];
                            $prefi=$res1[0]['prefijo'];//$res1->fields[4];
                          }

                          $result= $this->cnn->query('0', $sql_impri);//sgc $cnn->Execute($sql_impri);

                          if(count($result)>0)//($result->fields>0)
                          {
                            $nombre=$result[0]['operador'];//$result->fields[6];
                            $valor=$result[0]['valor'];//$result->fields[5];
                            $fecha_rel=$result[0]['fecha'];//$result->fields[2];
                            $fecha_r=$result[0]['fecha']." Hora :".$result[0]['hora'];//$result->fields[2]." Hora :".$result->fields[3];
                            $recaudador=$result[0]['usuario'];//$result->fields[7];
                            $pventa_u=$result[0]['nombre'];//$result->fields[9];
                            $pin=$result[0]['pin'];//$result->fields[13];
                            $celu=$result[0]['tel'];//$result->fields[4];
                            $name_plan=$result[0]['producto'];//$result->fields[11];
                            $cc=explode("CV", $recaudador);
                          }
                          else
                          {
                            $valor=$total;
                            $celu=$telefo;
                            $name_plan="Compra Recargas";
                          }
                          $sqlinser="insert into app_bemovil_recaudos(id_paq,id_ope,id_pro,tipo,descripcion,valor,usuario,id_recaudo,fecha_r,datos,imei)
                          values('$id_paq','$id_ope','$id_pro','$tipo_r','$name_plan','$valor','$nickname','$id_r','now()','".$celu."','".$imei."');";

                          $sq_hist="INSERT INTO hist_transacciones(tipo,descripcion,valor,usuario,id_transac,fecha_transa,status_nequi,datos)
                          values('1','Compra Recargas','-".$valor."','".$nickname."','".$id_r."','now()','0','Recargas Celular');";

                          try
                          {
                            unset($result);
                            $result= $this->cnn->query('19', $sqlinser);//app
                            $result= $this->cnn->query('19', $sq_hist);//app $cnn_app->Execute($sqlinser);

                            if($result)
                            {
                              $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ENTRO1");
                              array_push($val, array("status" =>1,"id_pago"=>$id_r,"nombre"=>$nombre,"total" =>number_format($valor,0,'.',','),"fecha_rel" =>$fecha_rel,"fechar" =>$fecha_r,"pventa_u" =>$pventa_u,"pin" =>$pin,"recaudador" =>$cc,"celu"=>$celu,"name_plan"=>$name_plan,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi));
                            }
                            else
                            {
                              unset($result);
                              $result= $this->cnn->query('19', $sqlinser);//app $cnn_app->Execute($sqlinser);
                              $result= $this->cnn->query('19', $sq_hist);//app
                              if($result)
                              {
                                $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ENTRO2");
                                array_push($val, array("status" =>1,"id_pago"=>$id_r,"nombre"=>$nombre,"total" =>number_format($valor,0,'.',','),"fecha_rel" =>$fecha_rel,"fechar" =>$fecha_r,"pventa_u" =>$pventa_u,"pin" =>$pin,"recaudador" =>$cc,"celu"=>$celu,"name_plan"=>$name_plan,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi));
                              }
                              else
                              {
                                $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ERROR1");
                                array_push($val, array("status" =>0,"msm"=>"Error: Transaccion exitosa, favor validar con soporte"));
                              }
                            }
                          }
                          catch (\Exception $e)
                          {
                            $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ERROR ".$e);
                            unset($result);
                            $result= $this->cnn->query('19', $sqlinser);//app $cnn_app->Execute($sqlinser);
                            if($result)
                            {
                              $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = CATCH1");
                              array_push($val, array("status" =>1,"id_pago"=>$id_r,"nombre"=>$nombre,"total" =>number_format($valor,0,'.',','),"fecha_rel" =>$fecha_rel,"fechar" =>$fecha_r,"pventa_u" =>$pventa_u,"pin" =>$pin,"recaudador" =>$cc,"celu"=>$celu,"name_plan"=>$name_plan,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi));
                            }
                            else
                            {
                              $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ERRORCATCH1");
                              array_push($val, array("status" =>0,"msm"=>"Error: Transaccion exitosa, favor validar con soporte"));
                            }
                          }
                        }
                        else if($con==3 && $dat_ws[0]=="0")//respuesta de  paquetes exitosa
                        {
                          $id_r=$dat_ws[1];
                          $sql_impri="select r.id,r.cant,r.fecha,r.hora,r.tel,r.valor,r.operador,r.usuario,r.pdv,t1.nombre,
                          o.descripcion as operador,p.descripcion as producto,t.descripcion as tipo_producto,r.pin
                          from bemovil_recargas_recaudado r,bemovil_tipo_paquete t,bemovil_paquetes p,bemovil_tipo_operador o ,territorios t1
                          where r.id='".$id_r."'
                          and r.id_operador=o.codigo
                          and p.tipo_paque=t.id
                          and p.id_paq=r.id_paq
                          and ('".date("Y-m-d", strtotime("-1 day"))."'>=o.fecha_ini and
                            '".date("Y-m-d", strtotime("-1 day"))."'<=o.fecha_fin  or '".date("Y-m-d", strtotime("-1 day"))."'>=o.fecha_ini and o.fecha_fin is null)
                          and r.id_operador=p.id_ope
                          and t1.codigo=r.pdv ";

                          $sql1="select nro_resolucion, fecha_resolucion, num_ini, num_fin, prefijo
                          from bemovil_resolucion_dian
                          where fecha_ini<='".date('Y-m-d')."' and (fecha_fin>='".date('Y-m-d')."' or fecha_fin is null)  ";

                          $res1= $this->cnn->query('0', $sql1);//sgc $cnn->Execute($sql1);
                                                  //echo $sql1;
                          if(count($res1)>0)//($res1->RecordCount()!=0)
                          {
                            $resu_dian=$res1[0]['nro_resolucion'];//$res1->fields[0];
                            $fecha_dian=$res1[0]['fecha_resolucion'];//$res1->fields[1];
                            $num_i=$res1[0]['num_ini'];//$res1->fields[2];
                            $num_f=$res1[0]['num_fin'];//$res1->fields[3];
                            $prefi=$res1[0]['prefijo'];//$res1->fields[4];
                          }
                          $result= $this->cnn->query('0', $sql_impri);//sgc $cnn->Execute($sql_impri);

                          if(count($result)>0)//($result->fields>0)
                          {
                            $nombre=$result[0]['operador'];//$result->fields[6];
                            $valor=$result[0]['valor'];//$result->fields[5];
                            $fecha_rel=$result[0]['fecha'];//$result->fields[2];
                            $fecha_r=$result[0]['fecha']." Hora :".$result[0]['hora'];//$result->fields[2]." Hora :".$result->fields[3];
                            $recaudador=$result[0]['usuario'];//$result->fields[7];
                            $pventa_u=$result[0]['nombre'];//$result->fields[9];
                            $pin=$result[0]['pin'];//$result->fields[13];
                            $celu=$result[0]['tel'];//$result->fields[4];
                            $name_plan=$result[0]['producto'];//$result->fields[11];
                            $cc=explode("CV", $recaudador);
                          }
                          else
                          {
                            $valor=$total;
                            $celu=$telefo;
                            $name_plan="Compra Paquetes";
                          }
                          $sqlinser="insert into app_bemovil_recaudos(id_paq,id_ope,id_pro,tipo,descripcion,valor,usuario,id_recaudo,fecha_r,datos,imei)
                          values('$id_paq','$id_ope','$id_pro','$tipo_r','$name_plan','$valor','$nickname','$id_r','now()','".$celu."','".$imei."');";

                          $sq_hist="INSERT INTO hist_transacciones(tipo,descripcion,valor,usuario,id_transac,fecha_transa,status_nequi,datos)
                          values('2','Compra Paquetes','-".$valor."','".$nickname."','".$id_r."','now()','0','Paquetes Celular');";
                          unset($result);
                          $result= $this->cnn->query('19', $sqlinser);//app $cnn_app->Execute($sqlinser);
                          $result = $this->cnn->query('19', $sq_hist);//app

                          try
                          {
                            if($result)
                            {
                              $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ENTRO1");
                              array_push($val, array("status" =>1,"id_pago"=>$id_r,"nombre"=>$nombre,"total" =>number_format($valor,0,'.',','),"fecha_rel" =>$fecha_rel,"fechar" =>$fecha_r,"pventa_u" =>$pventa_u,"pin" =>$pin,"recaudador" =>$cc,"celu"=>$celu,"name_plan"=>$name_plan,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi));
                            }
                            else
                            {
                              unset($result);
                              $result= $this->cnn->query('19', $sqlinser);//app $cnn_app->Execute($sqlinser);
                              $result= $this->cnn->query('19', $sq_hist);//app
                              if($result)
                              {
                                $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ENTRO2");
                                array_push($val, array("status" =>1,"id_pago"=>$id_r,"nombre"=>$nombre,"total" =>number_format($valor,0,'.',','),"fecha_rel" =>$fecha_rel,"fechar" =>$fecha_r,"pventa_u" =>$pventa_u,"pin" =>$pin,"recaudador" =>$cc,"celu"=>$celu,"name_plan"=>$name_plan,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi));
                              }
                              else
                              {
                                $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ERROR1");
                                array_push($val, array("status" =>0,"msm"=>"Error: Transaccion exitosa, favor validar con soporte"));
                              }
                            }
                          }
                          catch (\Exception $e)
                          {
                            $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ERROR ".$e);
                            unset($result);
                            $result= $this->cnn->query('19', $sqlinser);//app $cnn_app->Execute($sqlinser);
                            $result= $this->cnn->query('19', $sq_hist);//app
                            if($result)
                            {
                              $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ENTRO2");
                              array_push($val, array("status" =>1,"id_pago"=>$id_r,"nombre"=>$nombre,"total" =>number_format($valor,0,'.',','),"fecha_rel" =>$fecha_rel,"fechar" =>$fecha_r,"pventa_u" =>$pventa_u,"pin" =>$pin,"recaudador" =>$cc,"celu"=>$celu,"name_plan"=>$name_plan,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi));
                            }
                            else
                            {
                              $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ERROR1");
                              array_push($val, array("status" =>0,"msm"=>"Error: Transaccion exitosa, favor validar con soporte"));
                            }
                          }
                        }
                        else if($con==4 && $dat_ws[0]=="0")//respuesta de  runt exitosa
                        {
                          $id_r=$dat_ws[1];

                          $sql1="select nro_resolucion, fecha_resolucion, num_ini, num_fin, prefijo
                          from bemovil_resolucion_dian where fecha_ini<='".date('Y-m-d')."' and (fecha_fin>='".date('Y-m-d')."' or fecha_fin is null)  ";

                          $res1=$this->cnn->query('0', $sql1);//sgc $cnn->Execute($sql1);
                          //echo $sql1;

                          if(count($res1)>0)//($res1->RecordCount()!=0)
                          {
                            $resu_dian=$res1[0]['nro_resolucion'];//$res1->fields[0];
                            $fecha_dian=$res1[0]['fecha_resolucion'];//$res1->fields[1];
                            $num_i=$res1[0]['num_ini'];//$res1->fields[2];
                            $num_f=$res1[0]['num_fin'];//$res1->fields[3];
                            $prefi=$res1[0]['prefijo'];//$res1->fields[4];
                          }

                          $sql_id="select r.id,CAST(r.fecha_r as date) as fecha_r,r.valor,r.usuario,r.pdv,t.nombre,
                          h.descripcion as operador,r.pin,cast(r.fecha_r as time)  as hora,r.placa,r.dest_correo
                          from bemovil_hvehicular_recaudado r,territorios t,bemovil_hvehicular h
                          where r.id=".$id_r."
                          and t.codigo=r.pdv
                          and r.id_pro=h.id";
                          $res1=$this->cnn->query('0', $sql_id);//sgc $cnn->Execute($sql_id);
                          $valor="0";
                          if($res1)
                          {
                            $fecha_r=$res1[0]['fecha_r']; //$res1->fields[1];
                            //$valor= number_format($res1->fields[2],0,'.',',');
                            $valor= number_format($res1[0]['valor'],0,'.',',');
                            $usuario=$res1[0]['usuario'];//$res1->fields[3];
                            $operador=$res1[0]['operador'];//  $res1->fields[6];
                            $pin=$res1[0]['pin'];//   $res1->fields[7];
                            $hora=$res1[0]['hora'];//   $res1->fields[8];
                            $placa=$res1[0]['placa'];//   $res1->fields[9];
                            $mail=$res1[0]['dest_correo'];//$res1->fields[10];
                            $fechar=$fecha_r." Hora ".$hora;
                          }

                          $sqlinser="insert into app_bemovil_recaudos(id_paq,id_ope,id_pro,tipo,descripcion,valor,usuario,id_recaudo,fecha_r,datos,imei)
                          values('$id_paq','$id_ope','$id_pro','$tipo_r','$operador','".$valor."','$nickname','$id_r','now()','".$placa."|".$mail."','".$imei."');";
                          $sq_hist="INSERT INTO hist_transacciones(tipo,descripcion,valor,usuario,id_transac,fecha_transa,status_nequi,datos)
                          values('3','Compra Runt','-".$valor."','".$nickname."','".$id_r."','now()','0','Certificado Runt');";

                          unset($result);
                          $result=$this->cnn->query('19', $sqlinser);//app $cnn_app->Execute($sqlinser);
                          $result=$this->cnn->query('19', $sq_hist);//app
                          try
                          {
                            if($result)
                            {
                              $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ENTRO1");
                              array_push($val, array("status" =>1,"id_pago"=>$id_r,"fechar"=>$fechar,"fecha_rel"=>$fecha_r,"total"=>$valor,"recaudador"=>$usuario,"name_plan"=>$operador,"nombre"=>"Bemovil Runt","pin"=>$pin,"placa"=>$placa,"mail"=>$mail,"nro_fact"=>"0","resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi));
                            }
                            else
                            {
                              unset($result);
                              $result= $this->cnn->query('19', $sqlinser);//app $cnn_app->Execute($sqlinser);
                              $result=$this->cnn->query('19', $sq_hist);//app
                              if($result)
                              {
                                $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ENTRO2");
                                array_push($val, array("status" =>1,"id_pago"=>$id_r,"fechar"=>$fechar,"fecha_rel"=>$fecha_r,"total"=>$valor,"recaudador"=>$usuario,"name_plan"=>$operador,"nombre"=>"Bemovil Runt","pin"=>$pin,"placa"=>$placa,"mail"=>$mail,"nro_fact"=>"0","resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi));
                              }
                              else
                              {
                                $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ERROR1");
                                array_push($val, array("status" =>0,"msm"=>"Error: Transaccion exitosa, favor validar con soporte"));
                              }
                            }
                          }
                          catch (\Exception $e)
                          {
                            $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ERROR ".$e);
                            unset($result);
                            $result= $this->cnn->query('19', $sqlinser);//app $cnn_app->Execute($sqlinser);
                            $result= $this->cnn->query('19', $sq_hist);//app
                            if($result)
                            {
                              $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ENTRO2");
                              array_push($val, array("status" =>1,"id_pago"=>$id_r,"fechar"=>$fechar,"fecha_rel"=>$fecha_r,"total"=>$valor,"recaudador"=>$usuario,"name_plan"=>$operador,"nombre"=>"Bemovil Runt","pin"=>$pin,"placa"=>$placa,"mail"=>$mail,"nro_fact"=>"0","resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi));
                            }
                            else
                            {
                              $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ERROR1");
                              array_push($val, array("status" =>0,"msm"=>"Error: Transaccion exitosa, favor validar con soporte"));
                            }
                          }
                        }
                        else if($con==5 && $dat_ws[0]=="0")//respuesta de  pin exitosa
                        {
                          $id_r=$dat_ws[1];

                          $sql1="select nro_resolucion, fecha_resolucion, num_ini, num_fin, prefijo from bemovil_resolucion_dian
                          where fecha_ini<='".date('Y-m-d')."' and (fecha_fin>='".date('Y-m-d')."' or fecha_fin is null)  ";
                          $res1=$this->cnn->query('0', $sql1);//sgc $cnn->Execute($sql1);

                          if(count($res1)>0)//($res1->RecordCount()!=0)
                          {
                            $resu_dian=$res1[0]['nro_resolucion'];//$res1->fields[0];
                            $fecha_dian=$res1[0]['fecha_resolucion'];//$res1->fields[1];
                            $num_i=$res1[0]['num_ini'];//$res1->fields[2];
                            $num_f=$res1[0]['num_fin'];//$res1->fields[3];
                            $prefi=$res1[0]['prefijo'];//$res1->fields[4];
                          }

                          $sql_id="select r.id,CAST(r.fecha_r as date) as fecha_r,r.valor,r.usuario,r.pdv,t.nombre,h.concepto,r.pin,
                          cast(r.fecha_r as time)  as hora,r.correo,r.tele,r.nro_fact
                          from bemovil_pines_recaudado r,territorios t,bemovil_pines h
                          where r.id='".$id_r."'
                          and t.codigo=r.pdv
                          and r.id_pin=h.id";
                          $res1=$this->cnn->query('0', $sql_id);//sgc $cnn->Execute($sql_id);
                          $valor="0";
                          if($res1)
                          {
                            $fecha_r=$res1[0]['fecha_r'];//$res1->fields[1];
                            //$valor= number_format($res1->fields[2],0,'.',',');
                            $valor= number_format($res1[0]['valor'],0,'.',',');
                            $usuario=$res1[0]['usuario'];//   $res1->fields[3];
                            $operador=$res1[0]['concepto'];//  $res1->fields[6];
                            $pin=$res1[0]['pin'];//   $res1->fields[7];
                            $hora=$res1[0]['hora'];//   $res1->fields[8];
                            $mail=$res1[0]['correo'];//$res1->fields[9];
                            $placa=$res1[0]['tele'];//   $res1->fields[10];

                            $fechar=$fecha_r." Hora ".$hora;
                            $nro_fact=$res1[0]['nro_fact'];//$res1->fields[11];
                          }

                          $sqlinser="insert into app_bemovil_recaudos(id_paq,id_ope,id_pro,tipo,descripcion,valor,usuario,id_recaudo,fecha_r,datos,imei)
                          values('$id_paq','$id_ope','$id_pro','$tipo_r','$operador','".$valor."','$nickname','$id_r','now()','".$placa."|".$mail."','".$imei."');";

                          $sq_hist="INSERT INTO hist_transacciones(tipo,descripcion,valor,usuario,id_transac,fecha_transa,status_nequi,datos)
                          values('4','Compra Pines','-".$valor."','".$nickname."','".$id_r."','now()','0','".$operador."');";
                          unset($result);
                          $result= $this->cnn->query('19', $sqlinser);//app $cnn_app->Execute($sqlinser);
                          $result= $this->cnn->query('19', $sq_hist);//app
                          try
                          {
                            if($result)
                            {
                              $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." --".$sq_hist." | = ENTRO1");
                              array_push($val, array("status" =>1,"id_pago"=>$id_r,"fechar"=>$fechar,"fecha_rel"=>$fecha_r,"total"=>$valor,"recaudador"=>$usuario,"name_plan"=>$operador,"nombre"=>"Bemovil Pines","pin"=>$pin,"placa"=>$placa,"mail"=>$mail,"nro_fact"=>$nro_fact,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi));
                            }
                            else
                            {
                              unset($result);
                              $result= $this->cnn->query('19', $sqlinser);//app $cnn_app->Execute($sqlinser);
                              $result= $this->cnn->query('19', $sq_hist);//app
                              if($result)
                              {
                                $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ENTRO2");
                                array_push($val, array("status" =>1,"id_pago"=>$id_r,"fechar"=>$fechar,"fecha_rel"=>$fecha_r,"total"=>$valor,"recaudador"=>$usuario,"name_plan"=>$operador,"nombre"=>"Bemovil Pines","pin"=>$pin,"placa"=>$placa,"mail"=>$mail,"nro_fact"=>$nro_fact,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi));
                              }
                              else
                              {
                                $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ERROR1");
                                array_push($val, array("status" =>0,"msm"=>"Error: Transaccion exitosa, favor validar con soporte"));
                              }
                            }
                          }
                          catch (\Exception $e)
                          {
                            $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ERROR ".$e);
                            unset($result);
                            $result= $this->cnn->query('19', $sqlinser);//app $cnn_app->Execute($sqlinser);
                            $result= $this->cnn->query('19', $sq_hist);//app
                            if($result)
                            {
                              $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ENTRO2");
                              array_push($val, array("status" =>1,"id_pago"=>$id_r,"fechar"=>$fechar,"fecha_rel"=>$fecha_r,"total"=>$valor,"recaudador"=>$usuario,"name_plan"=>$operador,"nombre"=>"Bemovil Pines","pin"=>$pin,"placa"=>$placa,"mail"=>$mail,"nro_fact"=>$nro_fact,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi));
                            }
                            else
                            {
                              $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ERROR1");
                              array_push($val, array("status" =>0,"msm"=>"Error: Transaccion exitosa, favor validar con soporte"));
                            }
                          }
                        }
                        else
                        {
                          $msm="";
                          if(count($dat_ws)>1)
                          {
                            $msm="".$dat_ws[1]." ".$dat_ws[2];
                          }
                          else
                          {
                            $msm=$dat_ws[0];
                          }
                          array_push($val, array("status" =>0,"msm"=>$msm));
                        }
                      }
                      else
                      {
                        array_push($val, array("status" =>0,"msm"=>"LO SENTIMOS!, Hace falta Informacion para realizar su Transaccion."));
                      }
                    }
                    else
                    {
                      array_push($val, array("status" =>0,"msm"=>"LO SENTIMOS!, Su usuario esta inactivo o no Existe."));
                    }
                  }
                }
                else if($tipou=="1" || $tipou=="4")//cliente naturales o publicas
                {
                  $nickname_cliente="CV21177007";
                  $val_billetera="select valor from v_billetera_usuario where usuario='".$nickname."'";

                  $res_bille=$this->cnn->query('19', $val_billetera);//app $cnn_app->Execute($val_billetera);
                  if(count($res_bille)>0)//($res_bille->fields>0)
                  {
                    $saldo=(int)$res_bille[0]['valor'];//$res_bille->fields[0];
                  }
                  else
                  {
                    $saldo=0;
                  }
                  if(($saldo-(int)$total)>=0)
                  {
                    //se puede
                    $sql="select u.id from users u where u.mail='".$nickname."' and u.estado='A'";
                    $res_usu=$this->cnn->query('19', $sql);//app $cnn_app->Execute($sql);
                    if(count($res_usu)>0)//($res_usu->fields>0)
                    {
                      $vali=0;
                      if($tipo_r=="0")//recargas
                      {
                        $con=1;
                        $extras="?con=".$con."&ws_app=1&nickname=".$nickname_cliente."&ope=".$id_ope."&cel=".$telefo."&valor=".$total;
                        if($con=="" || $nickname_cliente=="" || $id_ope=="" || $telefo=="" || $total=="")
                        {
                          $vali=1;
                        }
                      }
                      else if($tipo_r=="1")//paquetes
                      {
                        $con=3;
                        $extras="?con=".$con."&ws_app=1&nickname=".$nickname_cliente."&ope=".$id_ope."&cel=".$telefo."&valor=".$total."&id_paquete=".$id_paq."";
                        if($con=="" || $nickname_cliente=="" || $id_ope=="" || $telefo=="" || $total=="" || $id_paq=="")
                        {
                          $vali=1;
                        }
                      }
                      else if($tipo_r=="2")//runt
                      {
                        $con=4;
                        $extras="?con=".$con."&ws_app=1&nickname=".$nickname_cliente."&placa=".$placa."&valor=".$total."&id_pro=".$id_pro."&correo=".$telefo."&tele=".$tele;
                        if($con=="" || $nickname=="" || $placa=="" || $total=="" || $id_pro=="" || $telefo=="")
                        {
                          $vali=1;
                        }
                      }
                      else if($tipo_r=="3")//pines
                      {
                        $con=5;
                        //con=5&id_paq="+id_paq+"&valor="+valor+"&id_ope="+id_ope+"&correo="+correo+"&tele="+tele+"&id_pro="+id_pro,
                        $extras="?con=".$con."&ws_app=1&nickname=".$nickname_cliente."&id_paq=".$id_paq."&valor=".$total."&id_ope=".$id_ope."&correo=".$telefo."&tele=".$tele."&id_pro=".$id_pro;
                        if($con=="" || $nickname=="" || $id_paq=="" || $total=="" || $id_ope=="" || $telefo=="" || $id_pro=="")
                        {
                          $vali=1;
                        }
                      }

                      if($vali==0)
                      {
                        try
                        {
                          $response = $this->http_client->request('GET', $url.$extras);//'https://api.github.com/repos/symfony/symfony-docs');
                          $statusCode = $response->getStatusCode();

                          if($statusCode == "200")
                          {
                            $contentType = $response->getHeaders()['content-type'][0];
                            $valor = $response->getContent();
                          }
                          else
                          {
                            $msm="Error Conexion Sgc: ".$response->getContent();//$e->getMessage();
                            $this->log->logs($msm,$response->getInfo('debug'));
                          }//fin llamada res sgc
                        }
                        catch (\Exception $e)
                        {
                          $this->log->logs("ERROR file_get_contents natural ".$e);
                        }
                        $valor = preg_replace('([^A-Za-z0-9 |])', '', $valor);
                        $this->log->logs("RESPUESTA SGC NATURAL ".$valor."|".$telefo);

                        $valor=$this->validarTransaccionBeMovil($valor,$telefo,$total,$nickname,$fecha_act,$id_ope,$placa,$id_pro,$tipo_r);

                        $dat_ws=explode("|",$valor);
                        $this->log->logs("RESPUESTA SGC NATURAL2 ".$valor."|".$telefo);
                        if($con==1  && $dat_ws[0]=="0")//respuesta de  recargas exitosa
                        {
                          //$cnn= new conn(0,1);//conexion sgc
                          $id_r=$dat_ws[1];
                          $sql_impri="select  r.id,r.cant,r.fecha,r.hora,r.tel,r.valor,r.operador,r.usuario,r.pdv,t1.nombre,
                          o.descripcion as operador,p.descripcion as producto,t.descripcion as tipo_producto,r.pin
                          from bemovil_recargas_recaudado r,bemovil_tipo_paquete t,bemovil_paquetes p,bemovil_tipo_operador o ,territorios t1
                          where r.id='".$id_r."'
                          and r.id_operador=o.codigo
                          and p.tipo_paque=t.id
                          and p.id_paq=r.id_paq
                          and r.id_operador=p.id_ope
                          and t1.codigo=r.pdv
                          and ('".date("Y-m-d", strtotime("-1 day"))."'>=o.fecha_ini and
                            '".date("Y-m-d", strtotime("-1 day"))."'<=o.fecha_fin  or '".date("Y-m-d", strtotime("-1 day"))."'>=o.fecha_ini and o.fecha_fin is null)";

                          $sql1="select nro_resolucion, fecha_resolucion, num_ini, num_fin, prefijo
                          from bemovil_resolucion_dian
                          where fecha_ini<='".date('Y-m-d')."' and (fecha_fin>='".date('Y-m-d')."' or fecha_fin is null)  ";

                          $res1=$this->cnn->query('0', $sql1);//sgc $cnn->Execute($sql1);
                                                  //echo $sql1;

                          if(count($res1)>0)//($res1->RecordCount()!=0)
                          {
                            $resu_dian=$res1[0]['nro_resolucion'];//$res1->fields[0];
                            $fecha_dian=$res1[0]['fecha_resolucion'];//$res1->fields[1];
                            $num_i=$res1[0]['num_ini'];//$res1->fields[2];
                            $num_f=$res1[0]['num_fin'];//$res1->fields[3];
                            $prefi=$res1[0]['prefijo'];//$res1->fields[4];
                          }

                          $result= $this->cnn->query('0', $sql_impri);//sgc $cnn->Execute($sql_impri);

                          if(count($result)>0)//($result->fields>0)
                          {
                            $nombre=$result[0]['operador'];//$result->fields[6];
                            $valor=$result[0]['valor'];//$result->fields[5];
                            $fecha_rel=$result[0]['fecha'];//$result->fields[2];
                            $fecha_r=$result[0]['fecha']." Hora :".$result[0]['hora'];//$result->fields[2]." Hora :".$result->fields[3];
                            $recaudador=$result[0]['usuario'];//$result->fields[7];
                            $pventa_u=$result[0]['nombre'];//$result->fields[9];
                            $pin=$result[0]['pin'];//$result->fields[13];
                            $celu=$result[0]['tel'];//$result->fields[4];
                            $name_plan=$result[0]['producto'];//$result->fields[11];
                            $cc=explode("CV", $recaudador);

                          }
                          $sqlinser="insert into app_bemovil_recaudos(id_paq,id_ope,id_pro,tipo,descripcion,valor,usuario,id_recaudo,fecha_r,datos,imei)
                          values('$id_paq','$id_ope','$id_pro','$tipo_r','$name_plan','$valor','$nickname','$id_r','now()','".$celu."','".$imei."');";

                          $sq_hist="INSERT INTO hist_transacciones(tipo,descripcion,valor,usuario,id_transac,fecha_transa,status_nequi,datos)
                          values('1','Compra Recargas','-".$valor."','".$nickname."','".$id_r."','now()','0','Recargas Celular');";
                          unset($result);
                          $result= $this->cnn->query('19', $sqlinser);//app $cnn_app->Execute($sqlinser);
                          $result= $this->cnn->query('19', $sq_hist);//app
                          try
                          {
                            if($result)
                            {
                              $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ENTRO1");
                              array_push($val, array("status" =>1,"id_pago"=>$id_r,"nombre"=>$nombre,"total" =>number_format($valor,0,'.',','),"fecha_rel" =>$fecha_rel,"fechar" =>$fecha_r,"pventa_u" =>$pventa_u,"pin" =>$pin,"recaudador" =>$cc,"celu"=>$celu,"name_plan"=>$name_plan,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi));
                            }
                            else
                            {
                              unset($result);
                              $result= $this->cnn->query('19', $sqlinser);//app $cnn_app->Execute($sqlinser);
                              $result= $this->cnn->query('19', $sq_hist);//app
                              if($result)
                              {
                                $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ENTRO2");
                                array_push($val, array("status" =>1,"id_pago"=>$id_r,"nombre"=>$nombre,"total" =>number_format($valor,0,'.',','),"fecha_rel" =>$fecha_rel,"fechar" =>$fecha_r,"pventa_u" =>$pventa_u,"pin" =>$pin,"recaudador" =>$cc,"celu"=>$celu,"name_plan"=>$name_plan,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi));

                              }
                              else
                              {
                                $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ERROR1");
                                array_push($val, array("status" =>0,"msm"=>"Error: Transaccion exitosa, favor validar con soporte"));
                              }
                            }
                          }
                          catch (\Exception $e)
                          {
                            $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ERROR ".$e);
                            unset($result);
                            $result= $this->cnn->query('19', $sqlinser);//app $cnn_app->Execute($sqlinser);
                            $result= $this->cnn->query('19', $sq_hist);//app
                            if($result)
                            {
                              $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ENTRO2");
                              array_push($val, array("status" =>1,"id_pago"=>$id_r,"nombre"=>$nombre,"total" =>number_format($valor,0,'.',','),"fecha_rel" =>$fecha_rel,"fechar" =>$fecha_r,"pventa_u" =>$pventa_u,"pin" =>$pin,"recaudador" =>$cc,"celu"=>$celu,"name_plan"=>$name_plan,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi));
                            }
                            else
                            {
                              $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ERROR1");
                              array_push($val, array("status" =>0,"msm"=>"Error: Transaccion exitosa, favor validar con soporte"));
                            }
                          }
                        }
                        else if($con==3 && $dat_ws[0]=="0")//respuesta de  paquetes exitosa
                        {
                          $id_r=$dat_ws[1];
                          $sql_impri="select  r.id,r.cant,r.fecha,r.hora,r.tel,r.valor,r.operador,r.usuario,r.pdv,t1.nombre,
                          o.descripcion as operador,p.descripcion as producto,t.descripcion as tipo_producto,r.pin
                          from bemovil_recargas_recaudado r,bemovil_tipo_paquete t,bemovil_paquetes p,bemovil_tipo_operador o ,territorios t1
                          where r.id='".$id_r."'
                          and r.id_operador=o.codigo
                          and p.tipo_paque=t.id
                          and p.id_paq=r.id_paq
                          and r.id_operador=p.id_ope
                          and t1.codigo=r.pdv
                          and ('".date("Y-m-d", strtotime("-1 day"))."'>=o.fecha_ini and
                            '".date("Y-m-d", strtotime("-1 day"))."'<=o.fecha_fin  or '".date("Y-m-d", strtotime("-1 day"))."'>=o.fecha_ini and o.fecha_fin is null)";

                          $sql1="select nro_resolucion, fecha_resolucion, num_ini, num_fin, prefijo
                          from bemovil_resolucion_dian
                          where fecha_ini<='".date('Y-m-d')."' and (fecha_fin>='".date('Y-m-d')."' or fecha_fin is null)  ";

                          $res1=$this->cnn->query('0', $sql1);//sgc $cnn->Execute($sql1);
                          //echo $sql1;

                          if(count($res1)>0)//($res1->RecordCount()!=0)
                          {
                            $resu_dian=$res1[0]['nro_resolucion'];//$res1->fields[0];
                            $fecha_dian=$res1[0]['fecha_resolucion'];//$res1->fields[1];
                            $num_i=$res1[0]['num_ini'];//$res1->fields[2];
                            $num_f=$res1[0]['num_fin'];//$res1->fields[3];
                            $prefi=$res1[0]['prefijo'];//$res1->fields[4];
                          }

                          $result= $this->cnn->query('0', $sql_impri);//sgc $cnn->Execute($sql_impri);

                          if(count($result)>0)//($result->fields>0)
                          {
                            $nombre=$result[0]['operador'];//$result->fields[6];
                            $valor=$result[0]['valor'];//$result->fields[5];
                            $fecha_rel=$result[0]['fecha'];//$result->fields[2];
                            $fecha_r=$result[0]['fecha']." Hora :".$result[0]['hora'];//$result->fields[2]." Hora :".$result->fields[3];
                            $recaudador=$result[0]['usuario'];//$result->fields[7];
                            $pventa_u=$result[0]['nombre'];//$result->fields[9];
                            $pin=$result[0]['pin'];//$result->fields[13];
                            $celu=$result[0]['tel'];//$result->fields[4];
                            $name_plan=$result[0]['producto'];//$result->fields[11];
                            $cc=explode("CV", $recaudador);
                          }
                          $sqlinser="insert into app_bemovil_recaudos(id_paq,id_ope,id_pro,tipo,descripcion,valor,usuario,id_recaudo,fecha_r,datos,imei)
                          values('$id_paq','$id_ope','$id_pro','$tipo_r','$name_plan','$valor','$nickname','$id_r','now()','".$celu."','".$imei."');";

                          $sq_hist="INSERT INTO hist_transacciones(tipo,descripcion,valor,usuario,id_transac,fecha_transa,status_nequi,datos)values('2','Compra Paquetes','-".$valor."','".$nickname."','".$id_r."','now()','0','Paquetes Celular');";
                          unset($result);
                          $result= $this->cnn->query('19', $sqlinser);//app $cnn_app->Execute($sqlinser);
                          $result= $this->cnn->query('19', $sq_hist);//app

                          try
                          {
                            if($result)
                            {
                              $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ENTRO1");
                              array_push($val, array("status" =>1,"id_pago"=>$id_r,"nombre"=>$nombre,"total" =>number_format($valor,0,'.',','),"fecha_rel" =>$fecha_rel,"fechar" =>$fecha_r,"pventa_u" =>$pventa_u,"pin" =>$pin,"recaudador" =>$cc,"celu"=>$celu,"name_plan"=>$name_plan,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi));
                            }
                            else
                            {
                              unset($result);
                              $result= $this->cnn->query('19', $sqlinser);//app $cnn_app->Execute($sqlinser);
                              $result= $this->cnn->query('19', $sq_hist);//app
                              if($result)
                              {
                                $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ENTRO2");
                                array_push($val, array("status" =>1,"id_pago"=>$id_r,"nombre"=>$nombre,"total" =>number_format($valor,0,'.',','),"fecha_rel" =>$fecha_rel,"fechar" =>$fecha_r,"pventa_u" =>$pventa_u,"pin" =>$pin,"recaudador" =>$cc,"celu"=>$celu,"name_plan"=>$name_plan,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi));
                              }
                              else
                              {
                                $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ERROR1");
                                array_push($val, array("status" =>0,"msm"=>"Error: Transaccion exitosa, favor validar con soporte"));
                              }
                            }
                          }
                          catch (\Exception $e)
                          {
                            $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ERROR ".$e);
                            unset($result);
                            $result= $this->cnn->query('19', $sqlinser);//app $cnn_app->Execute($sqlinser);
                            $result= $this->cnn->query('19', $sq_hist);//app
                            if($result)
                            {
                              $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ENTRO2");
                              array_push($val, array("status" =>1,"id_pago"=>$id_r,"nombre"=>$nombre,"total" =>number_format($valor,0,'.',','),"fecha_rel" =>$fecha_rel,"fechar" =>$fecha_r,"pventa_u" =>$pventa_u,"pin" =>$pin,"recaudador" =>$cc,"celu"=>$celu,"name_plan"=>$name_plan,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi));
                            }
                            else
                            {
                              $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ERROR1");
                              array_push($val, array("status" =>0,"msm"=>"Error: Transaccion exitosa, favor validar con soporte"));
                            }
                          }
                        }
                        else if($con==4 && $dat_ws[0]=="0")//respuesta de  runt exitosa
                        {
                          //$cnn= new conn(0,1);//conexion sgc
                          $id_r=$dat_ws[1];
                          $sql_id="select r.id,CAST(r.fecha_r as date) as fecha_r,r.valor,r.usuario,r.pdv,t.nombre,
                          h.descripcion as operador,r.pin,cast(r.fecha_r as time)  as hora,r.placa,r.dest_correo
                          from bemovil_hvehicular_recaudado r,territorios t,bemovil_hvehicular h
                          where r.id=".$id_r."
                          and t.codigo=r.pdv
                          and r.id_pro=h.id";
                          $res1=$this->cnn->query('0', $sql_id);//sgc $cnn->Execute($sql_id);
                          $valor="0";
                          if($res1)
                          {
                            $fecha_r=$res1[0]['fecha_r'];//   $res1->fields[1];
                            //$valor= number_format($res1->fields[2],0,'.',',');
                            $valor= number_format($res1[0]['valor'],0,'.',',');
                            $usuario=$res1[0]['usuario'];//   $res1->fields[3];
                            $operador=$res1[0]['operador'];//  $res1->fields[6];
                            $pin=$res1[0]['pin'];//   $res1->fields[7];
                            $hora=$res1[0]['hora'];//   $res1->fields[8];
                            $placa=$res1[0]['placa'];//   $res1->fields[9];
                            $mail=$res1[0]['correo'];//$res1->fields[10];//
                            $fechar=$fecha_r." Hora ".$hora;
                          }

                          $sqlinser="insert into app_bemovil_recaudos(id_paq,id_ope,id_pro,tipo,descripcion,valor,usuario,id_recaudo,fecha_r,datos,imei)
                          values('$id_paq','$id_ope','$id_pro','$tipo_r','$operador','".$valor."','$nickname','$id_r','now()','".$placa."|".$mail."','".$imei."');";

                          $sq_hist="INSERT INTO hist_transacciones(tipo,descripcion,valor,usuario,id_transac,fecha_transa,status_nequi,datos)
                          values('3','Compra a Runt','-".$valor."','".$nickname."','".$id_r."','now()','0','Certificado Runt');";
                          unset($result);
                          $result= $this->cnn->query('19', $sqlinser);//app $cnn_app->Execute($sqlinser);
                          $result= $this->cnn->query('19', $sq_hist);//app
                          try
                          {
                            if($result)
                            {
                              $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ENTRO1");
                              array_push($val, array("status" =>1,"id_pago"=>$id_r,"fechar"=>$fechar,"fecha_rel"=>$fecha_r,"total"=>$valor,"recaudador"=>$usuario,"name_plan"=>$operador,"nombre"=>"Bemovil Runt","pin"=>$pin,"placa"=>$placa,"mail"=>$mail));
                            }
                            else
                            {
                              unset($result);
                              $result= $this->cnn->query('19', $sqlinser);//app$cnn_app->Execute($sqlinser);
                              $result= $this->cnn->query('19', $sq_hist);//app
                              if($result)
                              {
                                $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ENTRO2");
                                array_push($val, array("status" =>1,"id_pago"=>$id_r,"fechar"=>$fechar,"fecha_rel"=>$fecha_r,"total"=>$valor,"recaudador"=>$usuario,"name_plan"=>$operador,"nombre"=>"Bemovil Runt","pin"=>$pin,"placa"=>$placa,"mail"=>$mail));
                              }
                              else
                              {
                                $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ERROR1");
                                array_push($val, array("status" =>0,"msm"=>"Error: Transaccion exitosa, favor validar con soporte"));
                              }
                            }
                          }
                          catch (\Exception $e)
                          {
                            $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ERROR ".$e);
                            unset($result);
                            $result= $this->cnn->query('19', $sqlinser);//app $cnn_app->Execute($sqlinser);
                            $result= $this->cnn->query('19', $sq_hist);//app
                            if($result)
                            {
                              $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ENTRO2");
                              array_push($val, array("status" =>1,"id_pago"=>$id_r,"fechar"=>$fechar,"fecha_rel"=>$fecha_r,"total"=>$valor,"recaudador"=>$usuario,"name_plan"=>$operador,"nombre"=>"Bemovil Runt","pin"=>$pin,"placa"=>$placa,"mail"=>$mail));
                            }
                            else
                            {
                              $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ERROR1");
                              array_push($val, array("status" =>0,"msm"=>"Error: Transaccion exitosa, favor validar con soporte"));
                            }
                          }
                        }
                        else if($con==5 && $dat_ws[0]=="0")//respuesta de  pin exitosa
                        {
                          //$cnn= new conn(0,1);//conexion sgc
                          $id_r=$dat_ws[1];

                          $sql1="select nro_resolucion, fecha_resolucion, num_ini, num_fin, prefijo
                          from bemovil_resolucion_dian
                          where fecha_ini<='".date('Y-m-d')."' and (fecha_fin>='".date('Y-m-d')."' or fecha_fin is null)  ";

                          $res1=$this->cnn->query('0', $sql1);//sgc $cnn->Execute($sql1);
                          //echo $sql1;

                          if(count($res1)>0)//($res1->RecordCount()!=0)
                          {
                            $resu_dian=$res1[0]['nro_resolucion'];//$res1->fields[0];
                            $fecha_dian=$res1[0]['fecha_resolucion'];//$res1->fields[1];
                            $num_i=$res1[0]['num_ini'];//$res1->fields[2];
                            $num_f=$res1[0]['num_fin'];//$res1->fields[3];
                            $prefi=$res1[0]['prefijo'];//$res1->fields[4];
                          }

                          $sql_id="select  r.id,CAST(r.fecha_r as date) as fecha_r,r.valor,r.usuario,r.pdv,t.nombre,h.concepto,r.pin,
                          cast(r.fecha_r as time)  as hora,r.correo,r.tele,r.nro_fact
                          from bemovil_pines_recaudado r,territorios t,bemovil_pines h
                          where r.id='".$id_r."'
                          and t.codigo=r.pdv
                          and r.id_pin=h.id";
                          $res1=$this->cnn->query('0', $sql_id);//sgc $cnn->Execute($sql_id);

                          $valor="0";
                          if($res1)
                          {
                            $fecha_r=$res1[0]['fecha_r'];//   $res1->fields[1];
                            //$valor= number_format($res1->fields[2],0,'.',',');
                            $valor= number_format($res1[0]['valor'],0,'.',',');
                            $usuario=$res1[0]['usuario'];//   $res1->fields[3];
                            $operador=$res1[0]['operador'];//  $res1->fields[6];
                            $pin=$res1[0]['pin'];//   $res1->fields[7];
                            $hora=$res1[0]['hora'];//   $res1->fields[8];
                            $placa=$res1[0]['placa'];//   $res1->fields[9];
                            $mail=$res1[0]['correo'];//$res1->fields[10];//
                            $fechar=$fecha_r." Hora ".$hora;
                            $nro_fact=$res1[0]['nro_fact'];//$res1->fields[11];
                          }

                          $sqlinser="insert into app_bemovil_recaudos(id_paq,id_ope,id_pro,tipo,descripcion,valor,usuario,id_recaudo,fecha_r,datos,imei)
                          values('$id_paq','$id_ope','$id_pro','$tipo_r','$operador','".$valor."','$nickname','$id_r','now()','".$placa."|".$mail."','".$imei."');";

                          $sq_hist="INSERT INTO hist_transacciones(tipo,descripcion,valor,usuario,id_transac,fecha_transa,status_nequi,datos)
                          values('4','Compra Pines','-".$valor."','".$nickname."','".$id_r."','now()','0','".$operador."');";
                          unset($result);
                          $result= $this->cnn->query('19', $sqlinser);//app $cnn_app->Execute($sqlinser);
                          $result= $this->cnn->query('19', $sq_hist);//app

                          try
                          {
                            if($result)
                            {
                              $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ENTRO1");
                              array_push($val, array("status" =>1,"id_pago"=>$id_r,"fechar"=>$fechar,"fecha_rel"=>$fecha_r,"total"=>$valor,"recaudador"=>$usuario,"name_plan"=>$operador,"nombre"=>"Bemovil Pines","pin"=>$pin,"placa"=>$placa,"mail"=>$mail,"nro_fact"=>$nro_fact,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi));
                            }
                            else
                            {
                              unset($result);
                              $result= $this->cnn->query('19', $sqlinser);//app $cnn_app->Execute($sqlinser);
                              $result= $this->cnn->query('19', $sq_hist);//app
                              if($result)
                              {
                                $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ENTRO2");
                                array_push($val, array("status" =>1,"id_pago"=>$id_r,"fechar"=>$fechar,"fecha_rel"=>$fecha_r,"total"=>$valor,"recaudador"=>$usuario,"name_plan"=>$operador,"nombre"=>"Bemovil Pines","pin"=>$pin,"placa"=>$placa,"mail"=>$mail,"nro_fact"=>$nro_fact,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi));
                              }
                              else
                              {
                                $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ERROR1");
                                array_push($val, array("status" =>0,"msm"=>"Error: Transaccion exitosa, favor validar con soporte"));
                              }
                            }
                          }
                          catch (\Exception $e)
                          {
                            $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ERROR ".$e);
                            unset($result);
                            $result= $this->cnn->query('19', $sqlinser);//app $cnn_app->Execute($sqlinser);
                            $result= $this->cnn->query('19', $sq_hist);//app
                            if($result)
                            {
                              $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ENTRO2");
                              array_push($val, array("status" =>1,"id_pago"=>$id_r,"fechar"=>$fechar,"fecha_rel"=>$fecha_r,"total"=>$valor,"recaudador"=>$usuario,"name_plan"=>$operador,"nombre"=>"Bemovil Pines","pin"=>$pin,"placa"=>$placa,"mail"=>$mail,"nro_fact"=>$nro_fact,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi));
                            }
                            else
                            {
                              $this->log->logs("ULTIMA VALIDACION BEMOVIL| ".$sqlinser." -- ".$sq_hist." | = ERROR1");
                              array_push($val, array("status" =>0,"msm"=>"Error: Transaccion exitosa, favor validar con soporte"));
                            }
                          }
                        }
                        else
                        {
                          $msm="";
                          if(count($dat_ws)>1)
                          {
                            $msm="".$dat_ws[1]." ".$dat_ws[2];
                          }
                          else
                          {
                            $msm=$dat_ws[0];
                          }
                          array_push($val, array("status" =>0,"msm"=>$msm));
                        }
                      }
                      else
                      {
                        array_push($val, array("status" =>0,"msm"=>"LO SENTIMOS!, Hace falta Informacion para realizar su Transaccion."));
                      }
                    }
                    else
                    {
                      array_push($val, array("status" =>0,"msm"=>"LO SENTIMOS!, Su usuario esta inactivo o no Existe."));
                    }
                  }
                  else
                  {
                    array_push($val, array("status" =>0,"msm"=>"Uy, no te alcanza. Intenta Recargar mas saldo."));
                  }
                }
                else
                {
                  array_push($val, array("status" =>0,"msm"=>"El tipo de usuario no tiene parametrizado para Vender."));
                }
              }
              if ($validacion==999)
              {
                array_push($val, array("status" =>0,"msm"=>"LO SENTIMOS, LA PLATAFORMA ESTA INACTIVA4"));
              }
              $this->log->logs("RESPUESTA: ", array($val));
              $this->log->logs("*******FIN PROCESO RECARGA BEMOVIL*******");
            }
            else if($con=="7")//ultimo bnet
            {
              $this->log->logs("*******Inicia Ultimo Bnet******");
              $datos=explode("|", $array);

              $nickname=$datos[0];//nickanme vendedora o usuario
              $carpeta=$_ENV['APP_ENV'];//basename(getcwd());
              $url=null;
              if($carpeta=="prod")//"ws_app")
              {
                //$url="http://10.1.1.4/serversoap/ws_app_pruebas/files/app-release.apk";
                $url="http://10.1.1.4:8094/uploads/files/prod/app-release.apk";
              }
              else//es pruebas
              {
                //$url="http://10.1.1.12/serversoap/ws_app_pruebas/files/app-release.apk";
                // $url="http://10.1.1.12:8094/uploads/files/dev/app-release.apk";
                $url=$this->ip.":8094/uploads/files/dev/app-release.apk";
              }

              if($url!="")
              {
                $path = $url;

                try
                {
                    $response = $this->http_client->request('GET', $path);//'https://api.github.com/repos/symfony/symfony-docs');
                    $statusCode = $response->getStatusCode();

                    $data = null;
                    if($statusCode == "200")
                    {
                      $data = $response->getContent();
                      $base64 = base64_encode($data);
                      array_push($val, array("status" =>1,"code_64"=>$base64,"name"=>"app_release.apk"));
                    }
                    else
                    {
                      $msm="Error Conexion Sgc: ".$response->getContent();//$e->getMessage();
                      $this->log->logs($msm,$response->getInfo('debug'));
                      array_push($val, array("status" =>0,"msm"=>"No se encontro archivo."));
                    }//fin llamada res sgc
                }
                catch(\Exception $e)
                {
                  $this->log->logs("error file get content ".$e);
                  array_push($val, array("status" =>0,"msm"=>$e));
                }
              }
              else
              {
                $base64="";
                array_push($val, array("status" =>0,"msm"=>"no existe la url"));
              }
              $this->log->logs("*******Finaliza Ultimo Bnet*******");
            }
            else if($con=="9")//ultimo consuertepay
            {
              $this->log->logs("********Inicia Ultimo Consuertepay*******");
              $datos=explode("|", $array);

              $nickname=$datos[0];//nickanme vendedora o usuario
              $carpeta=$_ENV['APP_ENV'];//basename(getcwd());
              if($carpeta=="prod")//"ws_app")
              {
                //$url="/var/www/serversoap/ws_app/files/Consuertepaytest.apk"
                //$url="../serversoap/ws_app/files/Consuertepaytest.apk";
                //$url="http://10.1.1.4/serversoap/ws_app/files/Consuertepaytest.apk";
                $url="http://10.1.1.4:8094/uploads/files/prod/Consuertepaytest.apk";
              }
              else//es pruebas
              {
                //$url="../serversoap/ws_app_pruebas/files/Consuertepaytest.apk";
                //$url="http://10.1.1.12/serversoap/ws_app_pruebas/files/Consuertepaytest.apk";
                // $url="http://10.1.1.12:8094/uploads/files/dev/Consuertepaytest.apk";
                $url=$this->ip.":8094/uploads/files/dev/Consuertepaytest.apk";
              }

              if($url!="")
              {
                $path = $url;
                try
                {
                  $response = $this->http_client->request('GET', $path);//'https://api.github.com/repos/symfony/symfony-docs');
                  $statusCode = $response->getStatusCode();

                  $data = null;
                  if($statusCode == "200")
                  {
                    $data = $response->getContent();
                    $base64 = base64_encode($data);
                    array_push($val, array("status" =>1,"code_64"=>$base64,"name"=>"Consuertepaytest.apks"));
                  }
                  else
                  {
                    $msm="Error Conexion Sgc: ".$response->getContent();//$e->getMessage();
                    $this->log->logs($msm,$response->getInfo('debug'));
                    array_push($val, array("status" =>0,"msm"=>"No se encontro archivo."));
                  }//fin llamada res sgc
                }
                catch(\Exception $e)
                {
                  $this->log->logs("error file get content ".$e);
                  array_push($val, array("status" =>0,"msm"=>$e));
                }
              }
              else
              {
                $base64="";
                array_push($val, array("status" =>0,"msm"=>"no existe la url"));
              }
              $this->log->logs("********Finaliza Ultimo Consuertepay********");
            }
            else if($con=="10")//resset passs funciona validar respuesta
            {
              $this->log->logs("********Inicia Reseteo Pass*******");
              $datos=explode("|", $array);

              $nickname=$datos[0];//nickanme vendedora o usuario
              $pass_act=$datos[1];
              $pass_1=$datos[2];
              $pass_2=$datos[3];

              if($nickname!="")
              {
                $sql="select u.id,u.pass as actual,md5('".$pass_1."') as pass1,md5('".$pass_2."') as pass2
                from users u where u.mail='".$nickname."' and u.estado='A'";
                $res_usu=$this->cnn->query('19', $sql);//app $cnn_app->Execute($sql);

                if(count($res_usu)>0)//($res_usu->fields>0)
                {
                  $pa=$res_usu[0]['actual'];//$res_usu->fields[1];
                  $p1=$res_usu[0]['pass1'];//$res_usu->fields[2];
                  $p2=$res_usu[0]['pass2'];//$res_usu->fields[3];

                  if($p1==$p2)
                  {
                    $sql2="select u.id from users u where u.mail='".$nickname."' and u.estado='A' and u.pass=md5('".$pass_act."')";
                    $res_2=$this->cnn->query('19', $sql2);//app $cnn_app->Execute($sql2);

                    if(count($res_2)>0)//($res_2->fields>0)
                    {
                      $sql_up="update users set pass='".$p1."' where mail='".$nickname."' returning id";

                      $res_up=$this->cnn->query('19', $sql_up);//app $cnn_app->Execute($sql_up);
                      if(count($res_up)>0)//($res_up->fields>0)
                      {
                        array_push($val, array("status" =>1,"msm"=> mb_convert_encoding("Contraseña Actualizada.",'utf8')));//utf8_encode("Contraseña Actualizada.")));
                      }
                      else
                      {
                        array_push($val, array("status" =>0,"msm"=>"Hubo un problema , intente mas tarde..."));
                      }
                    }
                    else
                    {
                      array_push($val, array("status" =>0,"msm"=> mb_convert_encoding("La contraseña actual no es Correcta.",'utf8')));//utf8_encode("La contraseña actual no es Correcta.")));
                    }
                  }
                  else
                  {
                    array_push($val, array("status" =>0,"msm"=>mb_convert_encoding("Las contraseñas nuevas no coinciden, intente nuevamente.",'utf8')));//utf8_encode("Las contraseñas nuevas no coinciden, intente nuevamente.")));
                  }
                }
                else
                {
                  array_push($val, array("status" =>0,"msm"=>"El usuario no esta activo."));
                }
              }
              else
              {
                array_push($val, array("status" =>0,"msm"=>"La sesiono Expiro, Vuelve a ingresar."));
              }
              $this->log->logs("RESPUESTA: ", array($val));
              $this->log->logs("********Fin Reseteo Pass*******");
            }
            else if($con=="11")//registra CC funciona
            {
              $this->log->logs("*******INICIA PROCESO REGISTRA CC*******");
              $datos=explode("|", $array);
              $nickname=$datos[0];//nickanme vendedora o usuario
              $cedula=trim($datos[1]);
              $tele=trim($datos[2]);
              if($cedula!="")//cedula
              {
                if($nickname!="")//login usuario
                {
                  $sql_up="UPDATE users set cc='".$cedula."' , tel='".$tele."' where mail='".$nickname."' returning id,cc";
                  $res_2=$this->cnn->query('19', $sql_up);//app $cnn_app->Execute($sql_up);
                  if(count($res_2)>0)//($res_2->fields>0)
                  {
                    array_push($val, array("status" =>1,"msm"=>"se realiza la actualizacion"));
                  }
                  else
                  {
                    array_push($val, array("status" =>0,"msm"=>"La sesiono Expiro, Vuelve a ingresar."));
                  }
                }
                else
                {
                  array_push($val, array("status" =>0,"msm"=>"La sesiono Expiro, Vuelve a ingresar."));
                }
              }
              else
              {
                array_push($val, array("status" =>0,"msm"=>"La sesiono Expiro, Vuelve a ingresar."));
              }
              $this->log->logs("*******FIN PROCESO REGISTRA CC*******");
            }
            else if($con=="14")//get hist transacciones user funsiona validar respuesta
            {
              $this->log->logs("*****************INICIA PROCESO GET HISTORICOS TRANSACCION****************");
              $datos=explode("|", $array);
              $nickname=$datos[0];//nickanme vendedora o usuario
              $imei=trim($datos[1]);
              $tipo_u=$datos[2];
              if($nickname!="")//valida sesion
              {
                $sql_hist="SELECT rec.* from
                ((SELECT r.id, r.valor, 'Compra Recargas' AS descripcion,r.estado,'0' AS status_nequi, r.usuario,r.id AS id_transac,'' AS status_code,
                CASE WHEN r.estado='0' THEN 'Confirmado' ELSE 'Con problemas'  END  AS status_msm,'' AS transactionid,cast(r.fecha_sys as date) AS dia,
                to_char(cast(r.fecha_sys AS TIME), 'HH24:MI:SS'):: TIME AS hora,'1' AS tipo_request,TO_CHAR(cast(r.fecha_sys AS date) :: DATE, 'dd TMMon, yyyy') AS fecha_grupo,r.fecha_sys,0 as tercero
                FROM bemovil_recargas_recaudado r
                WHERE r.usuario='".$nickname."' AND r.estado IN ('0') AND r.id_operador=(SELECT codigo FROM bemovil_tipo_operador WHERE tipo='0' AND codigo=r.id_operador and ('".date("Y-m-d", strtotime("-1 day"))."'>=fecha_ini and '".date("Y-m-d", strtotime("-1 day"))."'<=fecha_fin  or '".date("Y-m-d", strtotime("-1 day"))."'>=fecha_ini and fecha_fin is null)) order by r.fecha_sys desc)
                UNION ALL
                (SELECT r.id, r.valor, 'Compra Paquetes' AS descripcion,r.estado,'0' AS status_nequi, r.usuario,r.id AS id_transac,'' AS status_code,
                CASE WHEN r.estado='0' THEN 'Confirmado' ELSE 'Con problemas'  END  AS status_msm,'' AS transactionid,cast(r.fecha_sys as date) AS dia,
                to_char(cast(r.fecha_sys AS TIME), 'HH24:MI:SS'):: TIME AS hora,'2' AS tipo_request,TO_CHAR(cast(r.fecha_sys AS date) :: DATE, 'dd TMMon, yyyy') AS fecha_grupo,r.fecha_sys,0 as tercero
                FROM bemovil_recargas_recaudado r
                WHERE r.usuario='".$nickname."' AND r.estado IN ('0') AND r.id_operador=(SELECT codigo FROM bemovil_tipo_operador WHERE tipo='1' AND codigo=r.id_operador and ('".date("Y-m-d", strtotime("-1 day"))."'>=fecha_ini and
                '".date("Y-m-d", strtotime("-1 day"))."'<=fecha_fin  or '".date("Y-m-d", strtotime("-1 day"))."'>=fecha_ini and fecha_fin is null)) order by r.fecha_sys desc)
                UNION ALL
                (SELECT id, valor, 'Compra Runt' AS descripcion, estado, '0' AS status_nequi, usuario, id AS id_transac,'' AS status_code,
                CASE WHEN estado='0' THEN 'Confirmado' ELSE 'Con problemas'  END  AS status_msm,'' AS transactionid,cast(fecha_r as date) AS dia,
                to_char(cast(fecha_r AS TIME), 'HH24:MI:SS'):: TIME AS hora,'3' AS tipo_request,TO_CHAR(cast(fecha_r AS date) :: DATE, 'dd TMMon, yyyy') AS fecha_grupo,fecha_r AS fecha_sys,0 as tercero
                FROM bemovil_hvehicular_recaudado
                WHERE usuario='".$nickname."' AND estado IN ('0') order by fecha_r desc)
                UNION ALL
                (SELECT id, valor, 'Compra Pines' AS descripcion, estado, '0' AS status_nequi, usuario, id AS id_transac,'' AS status_code,
                CASE WHEN estado='0' THEN 'Confirmado' ELSE 'Con problemas'  END  AS status_msm,'' AS transactionid,cast(fecha_r as date) AS dia,
                to_char(cast(fecha_r AS TIME), 'HH24:MI:SS'):: TIME AS hora,'4' AS tipo_request,TO_CHAR(cast(fecha_r AS date) :: DATE, 'dd TMMon, yyyy') AS fecha_grupo,fecha_r AS fecha_sys,0 as tercero
                FROM bemovil_pines_recaudado
                WHERE usuario='".$nickname."' AND estado IN ('0') order by fecha_r desc)
                UNION ALL
                (SELECT id_deta_factura as id, valor_bruto as valor, 'Pago Servicios Publicos' AS descripcion, estado, '0' AS status_nequi, login, id_deta_factura AS id_transac,'' AS status_code,
                CASE WHEN estado='0' THEN 'Confirmado' ELSE 'Con problemas'  END  AS status_msm,'' AS transactionid,cast(fecha_recaudo as date) AS dia,
                hora,'5' AS tipo_request,TO_CHAR(cast(fecha_recaudo AS date) :: DATE, 'dd TMMon, yyyy') AS fecha_grupo,fecha_recaudo AS fecha_sys,id_proveedor as tercero
                FROM vista_recaudos_sgc WHERE login='".$nickname."' AND estado IN ('0') and id_proveedor in ('8','128','246','199','129','172') order by fecha_recaudo desc)
                UNION ALL
                (SELECT id_deta_factura as id, valor, 'Recargas Betplay' AS descripcion, estado, '0' AS status_nequi, usuario, id_deta_factura AS id_transac,'' AS status_code,
                CASE WHEN estado='0' THEN 'Confirmado' ELSE 'Con problemas'  END  AS status_msm,'' AS transactionid,cast(fecha_recaudado as date) AS dia,
                to_char(cast(fecha_recaudado AS TIME), 'HH24:MI:SS'):: TIME AS hora,'7' AS tipo_request,TO_CHAR(cast(fecha_recaudado AS date) :: DATE, 'dd TMMon, yyyy') AS fecha_grupo,fecha_recaudado AS fecha_sys,0 as tercero
                FROM cem_recaudado
                WHERE usuario='".$nickname."' AND estado IN ('0') and tipo='0' order by fecha_recaudado desc)
                UNION ALL
                (SELECT id_deta_factura as id, valor, 'Retiros Betplay' AS descripcion, estado, '0' AS status_nequi, usuario, id_deta_factura AS id_transac,'' AS status_code,
                CASE WHEN estado='0' THEN 'Confirmado' ELSE 'Con problemas'  END  AS status_msm,'' AS transactionid,cast(fecha_recaudado as date) AS dia,
                to_char(cast(fecha_recaudado AS TIME), 'HH24:MI:SS'):: TIME AS hora,'8' AS tipo_request,TO_CHAR(cast(fecha_recaudado AS date) :: DATE, 'dd TMMon, yyyy') AS fecha_grupo,fecha_recaudado AS fecha_sys,0 as tercero
                FROM cem_recaudado
                WHERE usuario='".$nickname."' AND estado IN ('0') and tipo='1' order by fecha_recaudado desc)) rec
                order by rec.fecha_sys desc limit 20;";
                $res=$this->cnn->query('0', $sql_hist);//sgc $cnn->Execute($sql_hist);

                if(count($res)>0)//($res->fields>0)
                {
                  $val_transa=array();
                  foreach ($res as $key => $value)
                  {
                    array_push($val_transa, array("id_hist"=>$value['id'],"valor"=>"$ ".number_format($value['valor'],0,'',','),"descripcion"=>$value['descripcion'],
                    "estado_trans"=>$value['estado'],"estado_nequi"=>$value['status_nequi'],"usuario"=>$value['usuario'],"id_consulta"=>$value['id_transac'].'|'.$value['tercero'],
                    "status_consulta"=>$value['status_code'],"msm_consulta"=>$value['status_msm'],"nequi_id"=>$value['transactionid'],"fecha_trans"=>$value['dia'],
                    "hora_trans"=>$value['hora'],"tipo_transacc"=>$value['tipo_request'],"fecha_grupo"=>$value['fecha_grupo']));
                  }
                  array_push($val, array("status" =>1,"msm"=>"Consultado Correctamente","transacciones"=>json_encode($val_transa)));
                }
                else if(count($res)==0)//($res->fields==0)
                {
                  $val_transa=array();
                  array_push($val, array("status" =>1,"msm"=>"Consultado Correctamente","transacciones"=>json_encode($val_transa)));
                }
                else
                {
                  array_push($val, array("status" =>0,"msm"=>"Uy , no se pudo, vuelve a intentar...."));
                }
              }
              else
              {
                array_push($val, array("status" =>0,"msm"=>"Su sesion ha Expirado, Vuelva a ingresar"));
              }
              $this->log->logs("*****************FIN PROCESO GET HISTORICOS TRANSACCION****************");
            }
            else if($con=="15")//get dinero actual //funciona falta probar usuario tipo 1 o 4
            {
              $this->log->logs("*****************INICIA PROCESO GET DINERO-SALDOS****************");
              $datos=explode("|", $array);

              $nickname=$datos[0];//nickanme vendedora o usuario
              $imei=trim($datos[1]);
              $sesion_mobil=trim($datos[2]);
              $tipo_user=trim($datos[3]);
              if($nickname!="")
              {
                if($tipo_user=="1" || $tipo_user=="4")//para usuarios o clientes naturales o tenderos
                {
                  $sql_val="select s.id from users u, users_sesion s where u.id=s.id_user and s.fechaf is null and u.estado='A'
                    and u.mail='".$nickname."' and token='".$sesion_mobil."';";
                  $res_=$this->cnn->query('19', $sql_val);//app $cnn_app->Execute($sql_val);
                  if(count($res_)>0)//($res_->fields>0)
                  {
                    $sql_v="select valor from v_billetera_usuario where usuario='".$nickname."'";
                    $re_v=$this->cnn->query('19', $sql_v);//app $cnn_app->Execute($sql_v);
                    if(count($re_v)>0)//($re_v->fields>0)
                    {
                      $valueBille=(int)$re_v[0]['valor'];//$re_v->fields[0];
                      array_push($val, array("status" =>1,"msm"=>"Consultado Correctamente","valor"=>number_format($valueBille,0,'',','),"valor2"=>$valueBille));
                    }
                    else if(count($re_v)==0)//($re_v->fields==0)
                    {
                      $valueBille=0;
                      array_push($val, array("status" =>1,"msm"=>"Consultado Correctamente","valor"=>number_format($valueBille,0,'',','),"valor2"=>$valueBille));
                    }
                    else
                    {
                      array_push($val, array("status" =>0,"msm"=>"Uy! Algo no salio bien."));
                    }
                  }
                  else
                  {
                    $valueBille=0;
                    array_push($val, array("status" =>1,"msm"=>"No se encontro Resultados de la sesion","valor"=>number_format($valueBille,0,'',','),"valor2"=>$valueBille));
                  }
                }
                else if ($tipo_user=="2")
                {
                  $valueBille=0;
                  $res=$this->h_c->saldo_tope_tat($nickname,$this->log);
                  $res1 = json_decode($res->getContent());
                  if($res1[0]->datos[0][0]!=null)
                  {
                    $tope = $res1[0]->datos[0][2];
                    $ventas = $res1[0]->datos[0][3];
                    $saldo = $res1[0]->datos[0][4];
                    array_push($val, array("status" =>1,"msm"=>"Consultado Correctamente","valor"=>number_format($saldo,0,'',','),"valor2"=>$saldo,
                    "ventas"=>number_format($ventas,0,'',','),"ventas2"=>(int) $ventas,"tope"=>number_format($tope,0,'',','),"tope2"=>$tope));
                  }
                  else array_push($val, array("status" =>1,"msm"=>"Consultado Correctamente","valor"=>number_format(0,0,'',','),"valor2"=>0,"ventas"=>number_format(0,0,'',','),"ventas2"=>0,"tope"=>number_format(0,0,'',','),"tope2"=>0));
                }
                else
                {
                  $valueBille=0;
                  array_push($val, array("status" =>1,"msm"=>"Consultado Correctamente","valor"=>number_format($valueBille,0,'',','),"valor2"=>$valueBille));
                }
              }
              else
              {
                array_push($val, array("status" =>0,"msm"=>"Su sesion ha Expirado, Vuelva a ingresar"));
              }
              $this->log->logs("RESPUESTA: ", array($val));
              $this->log->logs("*****************FIN PROCESO GET DINERO-SALDOS****************");
            }
            else if($con=="16")// actualizar usuario //funciona
            {
              $this->log->logs("*****************INICIA PROCESO ACTUALIZA USUARIO****************");
              $datos=explode("|", $array);

              $nickname=$datos[0];//nickanme vendedora o usuario
              $nombres=trim($datos[1]);
              $tele=trim($datos[2]);
              $cc=trim($datos[3]);
              $fnacio=trim($datos[4]);

              if($nickname!="")
              {
                $use_upd="UPDATE users set nombre='".$nombres."',tel='".$tele."',cc='".$cc."',fecha_nac='". $fnacio."' where mail='".$nickname."' returning id";
                $res_=$this->cnn->query('19', $use_upd);//app $cnn_app->Execute($use_upd);
                if(count($res_)>0)//($res_->fields>0)
                {
                  array_push($val, array("status" =>1,"msm"=>"Consultado Correctamente"));
                }
                else
                {
                  array_push($val, array("status" =>0,"msm"=>"Error sql"));
                }
              }
              else
              {
                array_push($val, array("status" =>0,"msm"=>"Su sesion ha Expirado, Vuelva a ingresar"));
              }
              $this->log->logs("*****************FIN PROCESO ACTUALIZA USUARIO****************");
            }
            else if($con=="17")//imprimir cierre del dia
            {
              $this->log->logs("*****************INICIA PROCESO CIERRE****************");
              $datos=explode("|", $array);

              $nickname=$datos[0];//nickanme vendedora o usuario
              $tipo_impo=trim($datos[1]);
              $metodo_imp=trim($datos[2]);
              $id_recaudo=trim($datos[3]);
              $id_convenio=trim($datos[4]);
              $reeimprime=trim($datos[5]);
              $tipo_u=trim($datos[6]);

              if($nickname!="")//valida sesion
              {
                $sql_hist="SELECT sum(r.valor) as valor, 'Compra Recargas' as descripcion, r.usuario from bemovil_recargas_recaudado r
                where r.usuario='".$nickname."' and cast(r.fecha_sys as date)='".date('Y-m-d')."' and r.estado in ('0') and
                r.id_operador=(select codigo from bemovil_tipo_operador where tipo='0' and codigo=r.id_operador and ('".date("Y-m-d", strtotime("-1 day"))."'>=fecha_ini and
                '".date("Y-m-d", strtotime("-1 day"))."'<=fecha_fin  or '".date("Y-m-d", strtotime("-1 day"))."'>=fecha_ini and fecha_fin is null)) group by r.usuario
                union all
                SELECT sum(r.valor) as valor, 'Compra Paquetes' as descripcion, r.usuario from bemovil_recargas_recaudado r
                where r.usuario='".$nickname."' and cast(r.fecha_sys as date)='".date('Y-m-d')."' and
                r.estado in ('0') and r.id_operador=(select codigo from bemovil_tipo_operador where tipo='1' and codigo=r.id_operador and ('".date("Y-m-d", strtotime("-1 day"))."'>=fecha_ini and
                '".date("Y-m-d", strtotime("-1 day"))."'<=fecha_fin  or '".date("Y-m-d", strtotime("-1 day"))."'>=fecha_ini and fecha_fin is null)) group by r.usuario
                union all
                SELECT sum(valor) as valor, 'Compra Runt' as descripcion, usuario from bemovil_hvehicular_recaudado
                where usuario='".$nickname."' and cast(fecha_r as date)='".date('Y-m-d')."' and estado in ('0') group by usuario
                union all
                SELECT sum(valor) as valor, 'Compra Pines' as descripcion, usuario from bemovil_pines_recaudado
                where usuario='".$nickname."' and cast(fecha_r as date)='".date('Y-m-d')."' and estado in ('0') group by usuario
                union all
                SELECT sum(valor) as valor, 'Recargas Betplay' as descripcion, usuario from cem_recaudado
                where usuario='".$nickname."' and cast(fecha_recaudado as date)='".date('Y-m-d')."' and estado in ('0') and tipo='0' group by usuario
                union all
                SELECT -sum(valor) as valor, 'Retiros Betplay' as descripcion, usuario from cem_recaudado
                where usuario='".$nickname."' and cast(fecha_recaudado as date)='".date('Y-m-d')."' and estado in ('0') and tipo='1' group by usuario
                union all
                SELECT coalesce(sum(y.venta_bruta), 0) as valor, 'Pago Servicios Publicos' as descripcion, y.usuario as usuario
                FROM (
                SELECT r.valor_recaudado AS venta_bruta, r.fecha_recaudo, r.usuario
                FROM emsa_recaudado r, emsa_regis_detalle d
                WHERE r.id_deta_factura = d.id and r.estado = '0'
                UNION ALL
                SELECT r.valor_recaudado AS venta_bruta, r.fecha_recaudo, r.usuario
                FROM edesa_recaudado r, edesa_regis_detalle d
                WHERE r.id_deta_factura = d.id and r.estado = '0'
                UNION ALL
                SELECT d.valor AS venta_bruta, r.fecha_recaudo, r.usuario
                FROM bioagricola_recaudado r, bioagricola_regis_detalle d
                WHERE r.id_deta_factura = d.id and r.estado = '0'
                UNION ALL
                SELECT r.valor_recaudado AS venta_bruta, r.fecha_recaudo, r.usuario
                FROM congente_recaudado r, congente_regis_detalle d
                WHERE r.id_deta_factura = d.id and r.estado = '0'
                UNION ALL
                SELECT d.valor_serviciop AS venta_bruta, r.fecha_recaudo, r.usuario
                FROM acueducto_recaudado r, acueducto_regis_detalle d
                WHERE r.id_deta_factura = d.id and r.estado = '0'
                UNION ALL
                SELECT d.valor_serviciop AS venta_bruta, r.fecha_recaudo, r.usuario
                FROM llanogas_recaudado_ws r, llanogas_regis_detalle_ws d
                WHERE r.id_deta_factura = d.id and r.estado = '0' AND d.tipo_convenio = 322
                UNION ALL
                SELECT d.valor_serviciop AS venta_bruta, r.fecha_recaudo, r.usuario
                FROM llanogas_recaudado_ws r, llanogas_regis_detalle_ws d
                WHERE r.id_deta_factura = d.id and r.estado = '0' AND d.tipo_convenio = 317
                UNION ALL
                SELECT d.valor_serviciop AS venta_bruta, r.fecha_recaudo, r.usuario
                FROM llanogas_recaudado r, llanogas_regis_detalle d
                WHERE r.id_deta_factura = d.id and r.estado = '0'
                UNION ALL
                SELECT d.valor_serviciop AS venta_bruta, r.fecha_recaudo, r.usuario
                FROM llanogas_recaudado r, llanogas_regis_detalle d
                WHERE r.id_deta_factura = d.id and r.estado = '0' AND d.valor_servicioa <> 0::numeric
                ) y
                WHERE date(y.fecha_recaudo)='".date('Y-m-d')."' and y.usuario='".$nickname."'
                group by y.usuario";
                $res=$this->cnn->query('0', $sql_hist);//sgc $cnn->Execute($sql_hist);
                $total_gen=0;
                if(count($res)>=0)//($res->fields>0)
                {
                  $val_transa=array();
                  if(count($res)>0)
                  {
                    foreach ($res as $key => $value)
                    {
                      $total_gen=($total_gen+((int)$value['valor']));
                      array_push($val_transa, array("valor"=>"$ ".number_format($value['valor'],0,'',','),"valor_numeric"=>$value['valor'],"descripcion"=>$value['descripcion'],"usuario"=>$value['usuario']));
                    }
                  }

                  //inicia productos gamble
                  $x = 0;
                  $pvt = 0;
                  $usuario = $nickname;
                  $v_70 = 0;
                  $v_80 = 0;
                  $fecha_act = date("Y-m-d");
                  $total_lf = '0';
                  $gru_v = '0';
                  $cedula = substr($usuario, 2);
                  $contenido = array();

                  $sql0 = "SELECT c.hraprs_ubcneg_trtrio_codigo as pventa,c.ubcntrtrio_codigo_compuesto_de as ccostos,
                  c.ubcntrtrio_codigo_compuesto__1 as zona
                  from controlhorariopersonas c,
                  (SELECT max(hhsalida) as salida from controlhorariopersonas where login='$usuario' and cast(cal_dia as date)='$fecha_act') t
                  where c.login='$usuario' and cast(c.cal_dia as date)='$fecha_act' and c.hhsalida=t.salida";
                  $res0 = $this->cnn->query('0', $sql0);

                  if(count($res0)>0)
                  {
                    $pv = $res0[0]['pventa'];
                    $cc = $res0[0]['ccostos'];
                    $zo = $res0[0]['zona'];
                    $pvt = $res0[0]['pventa'];
                  }

                  $compara = $this->h_c->comparaFecha($fecha_act, "2012-10-20");
                  if ($compara <= 0)
                  {
                    $sql = "SELECT dat_dto_codigo from otrosdias where dia=to_date('" . $fecha_act . "', 'YYYY-MM-DD') ";
                    $res = $this->cnn->query('2', $sql);
                    if(count($res)>0) $campoventa = "v.ventaneta";else $campoventa = "v.ventabruta";
                  }
                  else $campoventa = "v.ventabruta";

                  $sq2 = "SELECT sum ($campoventa) as venta from v_totalventasnegocio v
                  where v.recogedor='$usuario' and v.fecha BETWEEN to_date('$fecha_act', 'YYYY-MM-DD') AND to_date('$fecha_act', 'YYYY-MM-DD')";
                  $rs2 = $this->cnn->query('2', $sq2);
                  $rs2 = $this->cnn->query('13', $sq2);

                  if(count($rs2)>0) $v_70 = trim((string) $rs2[0]['VENTA']);
                  if(count($rs2)>0) $v_80 = trim((string) $rs2[0]['VENTA']);

                  $sql2 = "SELECT v.proveedor,v.servicio,s.nombrecorto,sum ($campoventa) as valor,v.fecha,v.ccosto,
                  v.sucursal,p.razonsocial,v.grupoventas,sum(v.formularios) as cant
                  from v_totalventasnegocio v, servicios s, proveedores p
                  where v.servicio=s.codigo and  v.recogedor='$usuario' and v.proveedor=p.nit and v.servicio not in ('2170','2180','2181')
                  and  v.fecha BETWEEN to_date('$fecha_act', 'YYYY-MM-DD') AND to_date('$fecha_act', 'YYYY-MM-DD')
                  group by v.proveedor,v.servicio,v.ccosto,v.sucursal,v.fecha,s.nombrecorto,p.razonsocial,v.grupoventas order by fecha,proveedor";
                  if ($v_70 >= $v_80) $res2 = $this->cnn->query('2', $sql2);else $res2 = $this->cnn->query('13', $sql2);

                  if(count($res2)>0)
                  {
                    unset($link);
                    foreach ($res2 as $row => $link)
                    {
                      $ser = trim((string) $link['SERVICIO']);
                      if ($ser == 2130 || $ser == 2180) $producto = $link['RAZONSOCIAL']; else $producto = $link['NOMBRECORTO'];
                      $cantidad = $link['CANT'];
                      $valor = $link['VALOR'];
                      $nom_pro = str_replace('LOTERIA ', '', $link['RAZONSOCIAL']);
                      $gru_v = $link['GRUPOVENTAS'];

                      if ($pvt == '2304' && ($ser == '510' || $ser == '511'))
                      {
                        //no se tiene encuenta para el pvta alkosto y la loteria fisica de gamble
                      }
                      else
                      {
                        $total_gen = $total_gen + $valor;
                        if ($ser == '510' || $ser == '511')
                        {
                          $producto = "LF " . $nom_pro;
                          $total_lf = $total_lf + $valor;
                          $cantidad = "";
                        }
                        if ($ser == '51') $producto = "LOTERIA EN LINEA";
                        array_push($val_transa, array("valor"=>"$ ".number_format($valor,0,'',','),"valor_numeric"=>$valor,"descripcion"=>substr($producto, 0, 17),"usuario"=>$nickname));
                      }
                    }

                    if ($gru_v != '0' && $total_lf != '0')
                    {
                      $sql = "SELECT distinct servicio,sacar_iva,por_resta,por_cnfp from porcentaje_comision_empresa
                      where fecha_ini<=to_date('$fecha_act', 'YYYY-MM-DD') and (fecha_fin>=to_date('$fecha_act', 'YYYY-MM-DD') or fecha_fin is null)
                      and servicio  in ('510')  ";
                      $res = $this->cnn->query('0', $sql);

                      unset($link);
                      foreach ($res as $row => $link)
                      {
                        $serv = $link['servicio'];
                        $sacar_iva = $link['sacar_iva'];
                        $com_ltn = $link['por_resta'];
                        $com_ltp = $link['por_cnfp'];
                        $cantidad = "";
                        if ($gru_v == 4 || $gru_v == 58) //prestacional
                        {
                          $valor = ($total_lf * $com_ltp) / 100;
                          $total_gen = $total_gen - $valor;
                          $producto = 'ANTICIPO LF';
                          array_push($val_transa, array("valor"=>"$ -".number_format($valor,0,'',','),"valor_numeric"=>$valor,"descripcion"=>substr($producto, 0, 17),"usuario"=>$nickname));
                        }
                        else if ($gru_v == 5 || $gru_v == 36 || $gru_v == 59 || $gru_v == 60 || $gru_v == 61 || $gru_v == 62) //comision normal y otros
                        {
                          $valor = ($total_lf * $com_ltn) / 100;
                          $total_gen = $total_gen - $valor;
                          $producto = 'ANTICIPO LF';
                          array_push($val_transa, array("valor"=>"$ -".number_format($valor,0,'',','),"valor_numeric"=>$valor,"descripcion"=>substr($producto, 0, 17),"usuario"=>$nickname));
                        }
                      }
                    }
                  }

                  $sqlb = "SELECT p.proveedor,p.servicio,s.nombrecorto,sum(p.totalpremio-p.retefuente) as valor,p.fechapago,
                  count(1) as cant, r.razonsocial
                  from premiospersonaproveedor p, servicios s, proveedores r where p.documentocajero='$cedula'
                  and p.fechapago=to_date('$fecha_act', 'YYYY-MM-DD') and p.servicio=s.codigo and p.proveedor=r.nit
                  and p.tipo_premio!='RSPREC'
                  group by p.proveedor,p.servicio,s.nombrecorto,p.fechapago,r.razonsocial";
                  $resb = $this->cnn->query('2', $sqlb);

                  unset($link);
                  foreach ($resb as $row => $link)
                  {
                    if ($link['SERVICIO'] == '2130') $producto = "PRE.PAG." . $link['RAZONSOCIAL'];
                    else $producto = "PRE.PAG." . $link['NOMBRECORTO'];

                    $cantidad = "";
                    $valor = $link['VALOR'];
                    $total_gen = $total_gen - $valor;
                    array_push($val_transa, array("valor"=>"$ -   ".number_format($valor,0,'',','),"valor_numeric"=>$valor,"descripcion"=>substr($producto, 0, 17),"usuario"=>$nickname));
                  }

                  array_push($val, array("status" =>1,"msm"=>"Consultado Correctamente","transacciones"=>json_encode($val_transa),"fecha"=>date('Y-m-d'),"hora"=>date('H:i:s'),"total_general"=>"$ ".number_format($total_gen,0,'',',')));
                }
                else
                {
                  array_push($val, array("status" =>0,"msm"=>"Uy , no se pudo, vuelve a intentar....."));
                }
              }
              else
              {
                array_push($val, array("status" =>0,"msm"=>"Su sesion ha Expirado, Vuelva a ingresar"));
              }
              $this->log->logs("*****************FIN PROCESO CIERRE****************");
            }
            else if($con=="21")//captura datos de una transaccion
            {
              $this->log->logs("*****************INICIA PROCESO GET TRANSACCION****************");
              $datos=explode("|", $array);

              $nickname=$datos[0];//nickanme vendedora o usuario
              $tipo_user=trim($datos[1]);//vendedora o externo
              $imei=trim($datos[2]);
              $tipo_trans=trim($datos[3]);//1,2,3,4,5 recargas,paquetes ,runt,pin,convenios
              $id_consulta=trim($datos[4]);
              $tercero=trim($datos[5]);
              $usuario_rec=trim($datos[6]);

              if($tipo_trans=="1" || $tipo_trans=="2")//recargas y paquetes
              {
                $id_r=$id_consulta;
                $sql_impri="SELECT  r.id,r.cant,r.fecha,r.hora,r.tel,r.valor,r.operador,r.usuario,r.pdv,t1.nombre,
                o.descripcion as operador,p.descripcion as producto,t.descripcion as tipo_producto,r.pin
                from bemovil_recargas_recaudado r,bemovil_tipo_paquete t,bemovil_paquetes p,bemovil_tipo_operador o ,territorios t1
                where r.id='".$id_consulta."'
                and r.id_operador=o.codigo
                and p.tipo_paque=t.id
                and p.id_paq=r.id_paq
                and r.id_operador=p.id_ope
                and t1.codigo=r.pdv
                and ('".date("Y-m-d", strtotime("-1 day"))."'>=o.fecha_ini and
                '".date("Y-m-d", strtotime("-1 day"))."'<=o.fecha_fin  or '".date("Y-m-d", strtotime("-1 day"))."'>=o.fecha_ini and o.fecha_fin is null)";

                $sql1="SELECT nro_resolucion, fecha_resolucion, num_ini, num_fin, prefijo
                from bemovil_resolucion_dian where fecha_ini<='".date('Y-m-d')."' and (fecha_fin>='".date('Y-m-d')."' or fecha_fin is null)  ";

                $res1=$this->cnn->query('0', $sql1);//sgc $cnn->Execute($sql1);
                //echo $sql1;
                if(count($res1)>0)//($res1->RecordCount()!=0)
                {
                  $resu_dian=$res1[0]['nro_resolucion'];//$res1->fields[0];
                  $fecha_dian=$res1[0]['fecha_resolucion'];//$res1->fields[1];
                  $num_i=$res1[0]['num_ini'];//$res1->fields[2];
                  $num_f=$res1[0]['num_fin'];//$res1->fields[3];
                  $prefi=$res1[0]['prefijo'];//$res1->fields[4];
                }
                $result= $this->cnn->query('0', $sql_impri);//sgc $cnn->Execute($sql_impri);

                if(count($result)>0)//($result->fields>0)
                {
                  $nombre=$result[0]['operador'];//$result->fields[6];
                  $valor=$result[0]['valor'];//$result->fields[5];
                  $fecha_rel=$result[0]['fecha'];//$result->fields[2];
                  $fecha_r=$result[0]['fecha']." Hora :".$result[0]['hora'];//$result->fields[2]." Hora :".$result->fields[3];
                  $recaudador=$result[0]['usuario'];//$result->fields[7];
                  $pventa_u=$result[0]['nombre'];//$result->fields[9];
                  $pin=$result[0]['pin'];//$result->fields[13];
                  $celu=$result[0]['tel'];//$result->fields[4];
                  $name_plan=$result[0]['producto'];//$result->fields[11];
                  $cc=explode("CV", $recaudador);

                  array_push($val, array("status" =>1,"id_pago"=>$id_r,"nombre"=>$nombre,"total" =>number_format($valor,0,'.',','),"fecha_rel" =>$fecha_rel,"fechar" =>$fecha_r,"pventa_u" =>$pventa_u,"pin" =>$pin,"recaudador" =>$cc,"celu"=>$celu,"name_plan"=>$name_plan,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi));
                }
                else
                {
                  array_push($val, array("status" =>0,"msm"=>"No se encontro Recaudo con el ID ".$id_consulta));
                }
              }
              else if($tipo_trans=="3")//runt
              {
                array_push($val, array("status" =>0,"msm"=>"en construccion"));
              }
              else if($tipo_trans=="4")//pines
              {
                array_push($val, array("status" =>0,"msm"=>"en construccion"));
              }
              else if($tipo_trans=="5")//convenios
              {
                $id_reg_pago=$id_consulta;
                if($tercero=="8")//si es emsa
                {
                  $sql_pago="select distinct r.id_deta_factura,t.nombre, r.pin,r.fecha_recaudo,d.id_factura, r.valor_recaudado, c.prs_documento
                  from emsa_recaudado r, emsa_regis_detalle d, territorios t, contratosventa c
                  where d.id='".$id_reg_pago."' and r.estado='0' and  r.id_deta_factura=d.id and r.punto_venta=t.codigo and r.usuario=c.login";

                  $r_p=$this->cnn->query('0', $sql_pago);//sgc $cnn->Execute($sql_pago);
                  if($r_p)
                  {
                    if(count($r_p)>0)//($r_p->fields>0)
                    {
                      $status="1";
                      $id_regis=$r_p[0]['id_deta_factura'];// $r_p->fields[0];
                      $fechar=$r_p[0]['fecha_recaudo'];//$r_p->fields[3];
                      $pin=$r_p[0]['pin'];//$r_p->fields[2];
                      $cliente=$r_p[0]['id_factura'];//$r_p->fields[4];
                      $total=number_format($r_p[0]['valor_recaudado'],0,'.',',');
                      array_push($val, array("status" =>$status,"id_pago"=>$id_regis,"fechar"=>$fechar,"pin"=>$pin,"cliente"=>$cliente,"total"=>$total,"id_tercero"=>$tercero));
                    }
                    else
                    {
                      array_push($val, array("status" =>0,"msm"=>"1.No se contraron recaudos con el ID ".$id_reg_pago));
                    }
                  }
                  else
                  {
                    array_push($val, array("status" =>0,"msm"=>"1.Ups, hubo un error, intente mas tarde..."));
                  }
                }
                else if($tercero=="129" || $tercero=="172")//llanogas
                {
                  $sql_pago="select distinct r.id_deta_factura,t.nombre, r.pin,r.fecha_recaudo,d.factura, d.valor_serviciop,
                  c.prs_documento, d.usuario,d.tipo_convenio, d.id_homologa,z.valor_serviciop as valor_serviciop2,z.factura as factura2,r.id
                  from llanogas_recaudado_ws r , llanogas_regis_detalle_ws d LEFT JOIN  llanogas_regis_detalle_ws z ON z.id=d.id_homologa , territorios t, contratosventa c
                  where d.id in ('".$id_reg_pago."')
                  and  r.id_deta_factura=d.id
                  and r.punto_venta=t.codigo
                  and r.usuario=c.login
                  and r.estado='0'";
                  $r_p=$this->cnn->query('0', $sql_pago);//sgc $cnn->Execute($sql_pago);
                  if($r_p)
                  {
                    if(count($r_p)>0)//($r_p->fields>0)
                    {
                      $status="1";
                      $id_regis=$r_p[0]['id_deta_factura'];// $r_p->fields[0];
                      $fechar=$r_p[0]['fecha_recaudo'];//$r_p->fields[3];
                      $pin=$r_p[0]['pin'];//$r_p->fields[2];
                      $cliente=$r_p[0]['factura']."-".$r_p[0]['factura2'];//$r_p->fields[4]."-".$r_p->fields[11];
                      $total_g=(int)$r_p[0]['valor_serviciop']+(int)$r_p[0]['valor_serviciop2'];
                      $total=number_format($total_g,0,'.',',');
                      array_push($val, array("status" =>$status,"id_pago"=>$id_regis,"fechar"=>$fechar,"pin"=>$pin,"cliente"=>$cliente,"total"=>$total,"id_tercero"=>$tercero));
                    }
                    else
                    {
                      array_push($val, array("status" =>0,"msm"=>"2.No se contraron recaudos con el ID ".$id_reg_pago));
                    }
                  }
                  else
                  {
                    array_push($val, array("status" =>0,"msm"=>"2.Ups, hubo un error, intente mas tarde..."));
                  }
                }
                else if($tercero=="128")//acueducto
                {
                  $sql_pago="select distinct r.id_deta_factura,t.nombre, r.pin,r.fecha_recaudo,d.factura, d.valor_serviciop,
                  c.prs_documento
                  from acueducto_recaudado r, acueducto_regis_detalle d, territorios t, contratosventa c
                  where d.id='".$id_reg_pago."' and r.estado='0' and r.id_deta_factura=d.id and r.punto_venta=t.codigo and r.usuario=c.login";
                  $r_p=$this->cnn->query('0', $sql_pago);//sgc $cnn->Execute($sql_pago);
                  if($r_p)
                  {
                    if(count($r_p)>0)//($r_p->fields>0)
                    {
                      $status="1";
                      $id_regis=$r_p[0]['id_deta_factura'];// $r_p->fields[0];
                      $fechar=$r_p[0]['fecha_recaudo'];//$r_p->fields[3];
                      $pin=$r_p[0]['pin'];//$r_p->fields[2];
                      $cliente=(int)$r_p[0]['factura'];//$r_p->fields[4];
                      $total=number_format($r_p[0]['valor_serviciop'],0,'.',',');
                      array_push($val, array("status" =>$status,"id_pago"=>$id_regis,"fechar"=>$fechar,"pin"=>$pin,"cliente"=>$cliente,"total"=>$total,"id_tercero"=>$tercero));
                    }
                    else
                    {
                      array_push($val, array("status" =>0,"msm"=>"3.No se contraron recaudos con el ID ".$id_reg_pago));
                    }
                  }
                  else
                  {
                    array_push($val, array("status" =>0,"msm"=>"3.Ups, hubo un error, intente mas tarde..."));
                  }
                }
                else if($tercero=="246")//EDESA
                {
                  $sql_pago="select distinct r.id_deta_factura,t.nombre, r.pin,r.fecha_recaudo,d.factura, r.valor_recaudado,
                  c.prs_documento,d.id_usuario
                  from edesa_recaudado r, edesa_regis_detalle d, territorios t, contratosventa c
                  where d.id='".$id_reg_pago."' and r.estado='0' and  r.id_deta_factura=d.id and r.punto_venta=t.codigo and r.usuario=c.login";
                  $r_p=$this->cnn->query('0', $sql_pago);//sgc $cnn->Execute($sql_pago);
                  if($r_p)
                  {
                    if(count($r_p)>0)//($r_p->fields>0)
                    {
                      $status="1";
                      $id_regis=$r_p[0]['id_deta_factura'];// $r_p->fields[0];
                      $fechar=$r_p[0]['fecha_recaudo'];//$r_p->fields[3];
                      $pin=$r_p[0]['pin'];//$r_p->fields[2];
                      $cliente=(int)$r_p[0]['factura'];//$r_p->fields[4];
                      $total=number_format($r_p[0]['valor_recaudado'],0,'.',',');
                      array_push($val, array("status" =>$status,"id_pago"=>$id_regis,"fechar"=>$fechar,"pin"=>$pin,"cliente"=>$cliente,"total"=>$total,"id_tercero"=>$tercero));
                    }
                    else
                    {
                      array_push($val, array("status" =>0,"msm"=>"4.No se contraron recaudos con el ID ".$id_reg_pago));
                    }
                  }
                  else
                  {
                    array_push($val, array("status" =>0,"msm"=>"4.Ups, hubo un error, intente mas tarde..."));
                  }
                }
                else if($tercero=="199")//congente
                {
                  $sql_pago="select distinct r.id_deta_factura,t.nombre, r.pin,r.fecha_recaudo,d.cedula, r.valor_recaudado,
                  c.prs_documento, d.nombres, r.prefijo_factura, r.factura_nro,r.valor_comision,r.valor_neto,r.iva, d.direccion,
                  d.telefono
                  from congente_recaudado r, congente_regis_detalle d, territorios t, contratosventa c
                  where d.id='".$id_reg_pago."' and r.estado='0' and  r.id_deta_factura=d.id and r.punto_venta=t.codigo and r.usuario=c.login ";
                  $r_p=$this->cnn->query('0', $sql_pago);//sgc $cnn->Execute($sql_pago);
                  if($r_p)
                  {
                    if(count($r_p)>0)//($r_p->fields>0)
                    {
                      $status="1";
                      $id_regis=$r_p[0]['id_deta_factura'];// $r_p->fields[0];
                      $fechar=$r_p[0]['fecha_recaudo'];//$r_p->fields[3];
                      $pin=$r_p[0]['pin'];//$r_p->fields[2];
                      $cliente=(int)$r_p[0]['cedula'];//$r_p->fields[4];
                      $total=number_format($r_p[0]['valor_recaudado'],0,'.',',');
                      array_push($val, array("status" =>$status,"id_pago"=>$id_regis,"fechar"=>$fechar,"pin"=>$pin,"cliente"=>$cliente,"total"=>$total));
                    }
                    else
                    {
                      array_push($val, array("status" =>0,"msm"=>"5.No se contraron recaudos con el ID ".$id_reg_pago));
                    }
                  }
                  else
                  {
                    array_push($val, array("status" =>0,"msm"=>"5.Ups, hubo un error, intente mas tarde..."));
                  }
                }
                else
                {
                  array_push($val, array("status" =>0,"msm"=>"6.No se encontro el id_tercero".$tercero));
                }
              }
              else if($tipo_trans=="7" || $tipo_trans=="8")//recargas  y retiros betplay
              {
                $id_r=$id_consulta;

                $sql_impri="select r.id,1 as cant,r.fecha_recaudado,cast(r.fecha_recaudado as time)as hora,r.id_apostador as tel,
                r.valor,'' as operador,r.usuario,r.pdv,t1.nombre,'' as operador1,
                CASE WHEN t.tipo=0 then 'Deposito Betplay' WHEN t.tipo=1 THEN 'Retiros Betplay' ELSE 'Otros Betplay' END  as producto,
                '' as tipo_producto,r.pin,t.tipo,r.nro_factura
                from cem_recaudado r,cem_regis_detalle t ,territorios t1
                where t.id='".$id_r."'
                and r.id_deta_factura=t.id
                and t1.codigo=r.pdv::integer
                and r.estado='0' ";

                $sql1="select nro_resolucion, fecha_resolucion, num_ini, num_fin, prefijo
                from bemovil_resolucion_dian where fecha_ini<='".date('Y-m-d')."' and (fecha_fin>='".date('Y-m-d')."' or fecha_fin is null)  ";
                $res1=$this->cnn->query('0', $sql1);//sgc $cnn->Execute($sql1);

                if(count($res1)>0)//($res1->RecordCount()!=0)
                {
                  $resu_dian=$res1[0]['nro_resolucion'];//$res1->fields[0];
                  $fecha_dian=$res1[0]['fecha_resolucion'];//$res1->fields[1];
                  $num_i=$res1[0]['num_ini'];//$res1->fields[2];
                  $num_f=$res1[0]['num_fin'];//$res1->fields[3];
                  $prefi=$res1[0]['prefijo'];//$res1->fields[4];
                }

                $result= $this->cnn->query('0', $sql_impri);//sgc $cnn->Execute($sql_impri);

                if(count($result)>0)//($result->fields>0)
                {
                  $nombre=$result[0]['operador'];//$result->fields[6];
                  $valor=$result[0]['valor'];//$result->fields[5];
                  $fecha_rel=$result[0]['fecha_recaudado'];//$result->fields[2];
                  $fecha_r=$result[0]['fecha_recaudado']." Hora :".$result[0]['hora'];//$result->fields[2]." Hora :".$result->fields[3];
                  $recaudador=$result[0]['usuario'];//$result->fields[7];
                  $pventa_u=$result[0]['nombre'];//$result->fields[9];
                  $pin=$result[0]['pin'];//$result->fields[13];
                  $celu=$result[0]['tel'];//$result->fields[4];
                  $name_plan=$result[0]['producto'];//$result->fields[11];
                  $tipo=$result[0]['tipo'];//$result->fields[14];
                  $nro_fact=$result[0]['nro_factura'];//$result->fields[15];
                  $cc=explode("CV", $recaudador);

                  array_push($val, array("status" =>1,"id_pago"=>$id_r,"nombre"=>$tipo,"total" =>number_format($valor,0,'.',','),"fecha_rel" =>$fecha_rel,"fechar" =>$fecha_r,"pventa_u" =>$pventa_u,"pin" =>$pin,"recaudador" =>$cc[1],"celu"=>$celu,"name_plan"=>$name_plan,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi,"nro_fact"=>$nro_fact));
                }
                else
                {
                  array_push($val, array("status" =>0,"msm"=>"No se encontro Recaudo con el ID ".$id_consulta));
                }
              }
              else
              {
                array_push($val, array("status" =>0,"msm"=>"No hay metodo disponible"));
              }
              $this->log->logs("*****************FIN PROCESO GET TRANSACCION****************");
            }
            else if($con=="22")//cerrando sesion //funciona
            {
              $this->log->logs("*****************INICIA PROCESO CIERRE SESION****************");
              $datos=explode("|", $array);

              $nickname=$datos[0];//nickanme vendedora o usuario
              $tipo_user=$datos[1];
              $token_sesion=$datos[2];
              $id_user="";
              $succes=0;
              $msm="";
              if($nickname!="")
              {
                $use="select id,mail from users where mail='".$nickname."'";
                $se_val=$this->cnn->query('19', $use);//app $cnn_app->Execute($use);
                if($se_val)
                {
                  if(count($se_val)>0)//($se_val->fields>0)
                  {
                    $id_user=$se_val[0]['id'];//$se_val->fields[0];
                    $select_s="select id_user,token,imeis,ips,name_device from users_sesion where id_user='".$id_user."'
                    and token='".$token_sesion."' and fechaf is null";

                    $se_res=$this->cnn->query('19', $select_s);//app $cnn_app->Execute($select_s);
                    if($se_res)
                    {
                      if(count($se_res)>0)//($se_res->fields>0)
                      {
                        $sql_update="update users_sesion set fechaf='now()' where id_user='".$id_user."' and fechaf is null";
                        $this->cnn->query('19', $sql_update);//app $cnn_app->Execute($sql_update);
                        $succes=1;
                        $msm="Cerro Sesion Correctamente";
                      }
                      else
                      {
                        $msm="No se encontraron sesion Abiertas.";
                        $succes=1;
                      }
                    }
                    else
                    {
                      $msm="Hubo un problema en el servicio,Intenta nuevamente.";
                    }
                  }
                  else
                  {
                    $msm="El usuario No existe. Comuniquese con soporte";
                  }
                }
                else
                {
                  $msm="Hubo un problema en el servicio,Intenta nuevamente.";
                }
              }
              else
              {
                $msm="No se pudo reconocer el Usuario. Vuelva a ingresar.";
              }
              array_push($val, array("status" =>$succes,"msm"=>$msm));
              $this->log->logs("*****************FIN PROCESO CIERRE SESION****************");
            }
            else if($con=="23")//REGISTRANDO NUEVA HUELLA CS10
            {
              $this->log->logs("****************INICIA REGISTRANDO NUEVA HUELLA*************");
              $datos=explode("|", $array);
              $nickname=$datos[0];
              $sesion=$datos[1];
              $tipo_login=$datos[2];
              $huella=$datos[3];
              $status="0";
              $msm="";
              try
              {
                $insert_huella="insert into users_huellas (code_finger,tipo_huella)values('".$huella."','".$tipo_login."')returning id;";
                $res_=$this->cnn->query('19', $insert_huella);//app $cnn_app->Execute($insert_huella);
                $id_huella=$res_[0]['id'];//$res_->fields[0];
                $upd_user="update users_sesion set id_huella='".$id_huella."',tipo_login='".$tipo_login."' where token='".$sesion."'";
                $this->cnn->query('19', $upd_user);//app $cnn_app->Execute($upd_user);
                $msm="Huella Registrada Exitosamente.";
                $status="1";
              }
              catch(\Exception $e)
              {
                $msm="Hubo un Error: ".$e->getMessage();
              }
              array_push($val, array("status" =>$status,"msm"=>$msm));
              $this->log->logs("****************FIN REGISTRANDO NUEVA HUELLA*************");
            }
            else if($con=="24")//recargar betplay
            {
              $this->log->logs("****************INICIA RECARGA BETPLAY*************");
              $datos=explode("|", $array);
              $nickname=$datos[0];
              $cc_apostador=$datos[1];
              $total=$datos[2];
              $producto=$datos[3];
              $user_tipo=$datos[4];
              $imei=$datos[5];
              $tipou=$user_tipo;

              $carpeta=$_ENV['APP_ENV'];//basename(getcwd());
              //echo $carpeta;
              $bandera=0;
              if($carpeta=="prod")//"ws_app")
              {
                $url="http://10.1.1.4/consuerteinventarios/cem_ws.php";
              }
              else//es pruebas
              {
                // $url="http://10.1.1.12/consuertepruebas/cem_ws.php";
                $url=$this->ip."/consuertepruebas/cem_ws.php";
              }
              $nickname_cliente=$nickname;

              unset($info);
              $info=[];
              if($user_tipo=="2" || $user_tipo=="5")//si es nivel vendedora o kiosco
              {
                $sql1="SELECT  distinct c.hraprs_ubcneg_trtrio_codigo as punto,c.ubcntrtrio_codigo_compuesto_de as ccostos,c.ubcntrtrio_codigo_compuesto__1 as zona
                from controlhorariopersonas c,contratosventa c2,usuarios c3
                where (c.login,c.cal_dia,c.hhentrada) in( select c4.login,c4.cal_dia,max(c4.hhentrada) as hhentrada from controlhorariopersonas c4,
                (select c3.login, max(c3.cal_dia) as cal_dia from controlhorariopersonas c3 where c3.login='".$nickname."'  group by c3.login ) v
                where c4.login=v.login
                and  c4.cal_dia=v.cal_dia
                group by c4.login,c4.cal_dia) and  c2.login=c.login and c2.fechafinal is null and c3.loginusr=c.login and c3.estado='A'";

                $st=$this->cnn->query('2', $sql1);//gamble_70 $cnn2->Execute($sql1);
                if($st)//(!empty($st[0]))
                {
                  if(count($st)>0)//($st->fields>0)
                  {
                    //usuario habilitado
                    $pdv_r=$st[0]['PUNTO'];//$st->fields[0];
                    $id_conve_sgc=470;//idtercero de betplay sgc
                    $sql_autoriza="select id_tercero from emsa_asignacion_punto
                    where punto_venta='".$pdv_r."' and id_tercero='".$id_conve_sgc."' and estado='A' ";
                    $res_autoriza=$this->cnn->query('0', $sql_autoriza);//sgc $cnn->Execute($sql_autoriza);

                    if($res_autoriza)
                    {
                      if(count($res_autoriza)>0)//($res_autoriza->fields>0)
                      {
                        if($user_tipo=="5")
                        {
                          if(!$this->doAuthenticate())
                          {
                            $permiso=1;
                            $info[0]=0;
                            $info[1]="Invalido Usuario o Password Webservice";
                          }
                          else
                          {
                            $permiso=0;
                            $info[0]=1;
                          }
                        }
                        else//para vendedora tipo=2
                        {
                          if($c_v<0)//si fecha actual es menor a la fecha de control de inicio sistema viejo
                          {
                            $resultados=$this->analizar_bloqueo($nickname,$total);
                            $info=explode("|",$resultados);
                          }
                          else
                          {
                            $permiso=0;
                            // $info[0]=1;
                            $res_val_usu = $this->validar_estado_usuario($nickname);
                            $info[0] = $res_val_usu["code"];
                            $info[1] = $res_val_usu["message"];
                          }
                        }

                        if($info[0]==1)
                        {
                          if($tipou<"5" )//si tipo usuario es menor a 5 $tipou
                          {
                            if($c_v<0)//si fecha actual es menor a la fecha de control de inicio sistema viejo
                            {
                              $min_rec=$info[3];
                              $max_rec=$info[4];
                              $vta_actual=$info[7];
                              $tope=$info[8];
                              $vta_calc=$info[9];
                              if((int)$total>=(int)$min_rec)//si la recarga es mayor o igual al minimo valor de la transaccion asignada al usuario
                              {
                                if((int)$total<=(int)$max_rec)//si la recarga es menor o igual al maximo valor de la transaccion asignada al usuario
                                {
                                  if((int)$vta_actual<=(int)$tope)//si total(recaudado+transaccion) es menor o igual al tope asignado al usuario
                                  {
                                    //si puede recargar el valor
                                  }
                                  else
                                  {
                                    $bandera=1;
                                    array_push($val, array("status" =>0,"msm"=>"6.La Recarga supera el monto permitido, su saldo es $".number_format((int)($tope-$vta_calc),0,'',',')));
                                  }
                                }
                                else
                                {
                                  $bandera=1;
                                  array_push($val, array("status" =>0,"msm"=>"7.El valor sobrepaso el limite permitido."));
                                }
                              }
                              else// la venta del dia supera el monto maximo permitido
                              {
                                $bandera=1;
                                array_push($val, array("status" =>0,"msm"=>"6.El valor es muy bajo."));
                              }
                            }
                            else//inicia sistema nuevo
                            {
                            }//fin sistema nuevo
                          }
                          else if($tipou=="5")//kiosco_sgc maquina
                          {
                            //si puede recargar el valor
                          }
                        }
                        else
                        {
                          $bandera=1;
                          array_push($val, array("status" =>0,"msm"=>$info[1]));
                        }
                      }
                      else
                      {
                        $bandera=1;
                        array_push($val, array("status" =>0,"msm"=>"4.Su Punto de venta no esta autorizado para BETPLAY."));
                      }
                    }
                    else
                    {
                      $bandera=1;
                      array_push($val, array("status" =>0,"msm"=>"3.No se pudo validar la autorizacion Intenta nuevamente."));
                    }
                  }
                  else
                  {
                    $bandera=1;
                    array_push($val, array("status" =>0,"msm"=>"1.Debe revisar su contrato o esta bloqueado.Contacte a soporte"));
                  }
                }
                else
                {
                  $bandera=1;
                  array_push($val, array("status" =>0,"msm"=>"2.Debe revisar su contrato o esta bloqueado.Contacte a soporte"));
                }

                if($bandera==999)
                {
                  array_push($val, array("status" =>0,"msm"=>"LO SENTIMOS, LA PLATAFORMA ESTA INACTIVA"));
                }

                if($bandera==0)
                {
                  $sql="select u.id from users u where u.mail='".$nickname."' and u.estado='A'";
                  $res_usu=$this->cnn->query('19', $sql);//app $cnn_app->Execute($sql);
                  if(count($res_usu)>0)//($res_usu->fields>0)
                  {
                    $con=1;
                    $extras="?con=".$con."&ws_app=1&nickname=".$nickname."&tc=CC&cc=".$cc_apostador."&valor=".$total."&id_tipo=0";
                    $this->log->logs("cem_ws sgc consulta = ".$url.$extras);

                    $response = $this->http_client->request('GET', $url.$extras);//'https://api.github.com/repos/symfony/symfony-docs');
                    $statusCode = $response->getStatusCode();
                    $resp=null;

                    if($statusCode == "200")
                    {
                      $contentType = $response->getHeaders()['content-type'][0];
                      $resp = $response->getContent();
                    }
                    else
                    {
                      $msm="Error Conexion Sgc: ".$response->getContent();//$e->getMessage();
                      $this->log->logs($msm,$response->getInfo('debug'));
                    }//fin llamada res sgc

                    $this->log->logs("RESPUESTA SGC BETPLAY ". $resp);
                    $dat_ws=explode("|", $resp);

                    if($dat_ws[0]=="1")//respuesta de consulta Exitosa
                    {
                      $con=2;
                      $extras="?con=".$con."&ws_app=1&nickname=".$nickname."&tc=CC&cuenta=".$cc_apostador."&valor=".$total.
                      "&pdv=".$pdv_r."&fechapeticion=".$fechapeticion."&horapeticion=".$horapeticion;
                      $this->log->logs("cem_ws sgc recaudo  = ".$url.$extras);

                      ini_set('default_socket_timeout', 500);
                      $response = $this->http_client->request('GET', $url.$extras);//'https://api.github.com/repos/symfony/symfony-docs');
                      $statusCode = $response->getStatusCode();
                      $resp2 = null;
                      if($statusCode == "200")
                      {
                        $contentType = $response->getHeaders()['content-type'][0];
                        $resp2 = $response->getContent();
                      }
                      else
                      {
                        $msm="Error Conexion Sgc: ".$response->getContent();//$e->getMessage();
                        $this->log->logs($msm,$response->getInfo('debug'));
                      }//fin llamada res sgc

                      $this->log->logs("RESPUESTA SGC BETPLAY2 ". $resp2);
                      $dat_ws2=explode("|", $resp2);

                      if($dat_ws2[0]=="1")//respuesta de recarga Exitosa
                      {
                        $id_r=$dat_ws2[2];//id registro
                        $sql_impri="select c.usuario,
                        (select nombres||' '||apellido1 from personas where documento::text=substring(c.usuario,3)) as nm_vendedor,
                        c.pdv,t.nombre,c.id_apostador,c.valor,cast(c.fecha_recaudado as date) as fecha_r,cast(c.fecha_recaudado as time) as hora,
                        c.pin,c.pin_betplay ,
                        CASE WHEN r.tipo =0 THEN 'Deposito Betplay' WHEN r.tipo =1 THEN 'Retiros Betplay' WHEN r.tipo =2
                        THEN 'Solicitud Retiro Pin' WHEN r.tipo =3 THEN 'Apuestas Rapidas' ELSE 'Producto desconocido' END as tipo_pro,
                        r.tipo,c.nro_factura
                        from cem_regis_detalle r,cem_recaudado c ,territorios t
                        where r.id=c.id_deta_factura::integer
                        and c.estado='0'
                        and t.codigo=c.pdv::integer
                        and r.tipo='0'
                        and c.id='".$id_r."'";

                        $sql1="select nro_resolucion, fecha_resolucion, num_ini, num_fin, prefijo
                        from bemovil_resolucion_dian
                        where fecha_ini<='".date('Y-m-d')."' and (fecha_fin>='".date('Y-m-d')."' or fecha_fin is null)  ";

                        $res1=$this->cnn->query('0', $sql1);//sgc $cnn->Execute($sql1);
                        //echo $sql1;
                        if(count($res1)>0)//($res1->RecordCount()!=0)
                        {
                          $resu_dian=$res1[0]['nro_resolucion'];//$res1->fields[0];
                          $fecha_dian=$res1[0]['fecha_resolucion'];//$res1->fields[1];
                          $num_i=$res1[0]['num_ini'];//$res1->fields[2];
                          $num_f=$res1[0]['num_fin'];//$res1->fields[3];
                          $prefi=$res1[0]['prefijo'];//$res1->fields[4];
                        }
                        $result= $this->cnn->query('0', $sql_impri);//app $cnn->Execute($sql_impri);

                        if(count($result)>0)//($result->fields>0)
                        {
                          $nombre=$result[0]['nm_vendedor'];//$result->fields[1];
                          $valor=$result[0]['valor'];//$result->fields[5];
                          $fecha_rel=$result[0]['fecha_r'];//$result->fields[6];
                          $fecha_r=$result[0]['fecha_r']." Hora :".$result[0]['hora'];//$result->fields[6]." Hora :".$result->fields[7];
                          $recaudador=$result[0]['usuario'];//$result->fields[0];
                          $pventa_u=$result[0]['nombre'];//$result->fields[3];
                          $pin=$result[0]['pin'];//$result->fields[8];
                          $celu=$result[0]['id_apostador'];//$result->fields[4];
                          $name_plan=$result[0]['tipo_pro'];//$result->fields[10];
                          $cc=explode("CV", $recaudador);
                          $nro_fact=$result[0]['nro_factura'];//$result->fields[12];
                        }
                        else
                        {
                          $valor=$total;
                          $celu=$cc_apostador;//$telefo;
                          $name_plan="Deposito Betplay";
                        }
                        array_push($val, array("status" =>1,"id_pago"=>$id_r,"nombre"=>$nombre,"total" =>number_format($valor,0,'.',','),"fecha_rel" =>$fecha_rel,"fechar" =>$fecha_r,"pventa_u" =>$pventa_u,"pin" =>$pin,"recaudador" =>$cc[1],"celu"=>$celu,"name_plan"=>$name_plan,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi,"nro_fact"=>$nro_fact));
                      }
                      else
                      {
                        $msm="";
                        if(count($dat_ws2)>1)
                        {
                          /* $message = trim(substr($dat_ws2[1], 55, 20));
                          if(strlen($message)>0)
                            $msm="".$dat_ws2[0]."-".$message;
                          else */
                          $msm="".$dat_ws2[0]."-".$dat_ws2[1];
                        }
                        else $msm=$dat_ws2[0];
                        array_push($val, array("status" =>0,"msm"=>$msm));

                      }
                    }
                    else if($dat_ws[0]=="999")
                    {
                      $msm=$dat_ws[1];
                      $arr_msm=explode(",", $msm);
                      if(count($arr_msm)>1) $msm= "Saldo insuficiente, ".$arr_msm[1];
                      if(empty($dat_ws[1])) $msm="Error sin identificar , contacte al administrador del sistema.";
                      array_push($val, array("status" =>0,"msm"=>$msm));
                    }
                    else
                    {
                      $msm="";
                      if(count($dat_ws)>1)
                      {
                        /* $message = trim(substr($dat_ws[1], 55, 20));
                        if(strlen($message)>0)
                          $msm="".$dat_ws[0]."-".$message;
                        else */
                        $msm="".$dat_ws[0]."-".$dat_ws[1];
                      }
                      else $msm=$dat_ws[0];
                      array_push($val, array("status" =>0,"msm"=>$msm));
                    }
                  }
                  else
                  {
                    array_push($val, array("status" =>0,"msm"=>"LO SENTIMOS!, Su usuario esta inactivo o no Existe."));
                  }
                }
              }
              else if($user_tipo=="1" || $user_tipo=="4")//si es externo o tendero
              {
                //$cnn_app= new conn(0,0);//conexion bd app
                $val_billetera="select valor from v_billetera_usuario where usuario='".$nickname."'";

                $res_bille=$this->cnn->query('19', $val_billetera);//app $cnn_app->Execute($val_billetera);
                if(count($res_bille)>0)//($res_bille->fields>0)
                {
                  $saldo=(int)$res_bille[0]['valor'];//$res_bille->fields[0];
                }
                else
                {
                  $saldo=0;
                }
                if(($saldo-(int)$total)>=0)
                {
                  $sql="select u.id from users u where u.mail='".$nickname."' and u.estado='A'";
                  $res_usu=$this->cnn->query('19', $sql);//app $cnn_app->Execute($sql);
                  if(count($res_usu)>0)//($res_usu->fields>0)
                  {
                    $nickname_cliente="CV21240220";//CV21240220
                    $sql1="SELECT  distinct c.hraprs_ubcneg_trtrio_codigo as punto,c.ubcntrtrio_codigo_compuesto_de as ccostos,c.ubcntrtrio_codigo_compuesto__1 as zona
                    from controlhorariopersonas c,contratosventa c2,usuarios c3
                    where (c.login,c.cal_dia,c.hhentrada) in( select c4.login,c4.cal_dia,max(c4.hhentrada) as hhentrada from controlhorariopersonas c4,
                    (select c3.login, max(c3.cal_dia) as cal_dia from controlhorariopersonas c3 where c3.login='".$nickname_cliente."'  group by c3.login ) v
                    where c4.login=v.login
                    and  c4.cal_dia=v.cal_dia
                    group by c4.login,c4.cal_dia) and  c2.login=c.login and c2.fechafinal is null and c3.loginusr=c.login and c3.estado='A'";

                    $st=$this->cnn->query('0', $sql1);//sgc $cnn->Execute($sql1);
                    if($st)
                    {
                      if(count($st)>0)//($st->fields>0)
                      {
                        //usuario habilitado
                        $pdv_r=$st[0]['punto'];//$st->fields[0];
                        $id_conve_sgc=470;//idtercero de betplay sgc
                        $sql_autoriza="select id_tercero from emsa_asignacion_punto where punto_venta='".$pdv_r."' and id_tercero='".$id_conve_sgc."' and estado='A' ";
                        $res_autoriza=$this->cnn->query('0', $sql_autoriza);//sgc $cnn->Execute($sql_autoriza);
                        if($res_autoriza)
                        {
                          if(count($res_autoriza)>0)//($res_autoriza->fields>0)
                          {
                            //$cnn_app= new conn(0,0);//conexion bd app
                            $sq_control="select id_modulo,min_rec,max_rec,min_ret,max_ret,
                            (select CASE when sum(valor) is null then 0 else sum(valor) end
                            from cem_recaudado
                            where usuario='".$nickname."' and cast(fecha_recaudado as date)='".date('Y-m-d')."' and tipo='0') as vta_actual
                            from users_parametros_recaudos where cast(fecha_ini as date)<='".date('Y-m-d')."'
                            and (cast(fecha_fin as date)>='".date('Y-m-d')."' or fecha_fin is null) and id_modulo='11' and usuario='".$nickname."'";
                            $control=$this->cnn->query('19', $sq_control);//app $cnn_app->Execute($sq_control);
                            if($control)
                            {
                              if(count($control)>0)//($control->fields>0)//hubo resultados el usuario tiene credenciales para
                              {
                                $min_rec=$control[0]['min_rec'];//$control->fields[1];
                                $max_rec=$control[0]['max_rec'];//$control->fields[2];
                                //$vta_actual=(int)$control->fields[5]+(int)$total;
                                $vta_actual=(int)$control[0]['vta_actual']+(int)$total;
                              }
                              else// defaul
                              {
                                $min_rec=2000;
                                $max_rec=5000000;
                                $sql_vta="select CASE when sum(valor) is null then 0 else sum(valor) end as valor from cem_recaudado
                                where usuario='".$nickname."' and cast(fecha_recaudado as date)='".date('Y-m-d')."' and tipo='0'";
                                $control2=$this->cnn->query('19', $sql_vta);//app $cnn_app->Execute($sql_vta);
                                //$vta_actual=(int)$control2->fields[0]+(int)$total;
                                $vta_actual=(int)$control2[0]['valor']+(int)$total;
                              }
                              //$min_ret=$control->fields[3];
                              //$max_ret=$control->fields[4];
                              //validamos monto venta actual no supere el limite
                              if((int)$total>=(int)$min_rec)//venta actual
                              {
                                if((int)$total<=(int)$max_rec)
                                {
                                  if((int)$vta_actual<=(int)$max_rec)//si total esta entre el valor minimo y max seguir
                                  {
                                    //si puede recargar el valor
                                  }
                                  else
                                  {
                                    $bandera=1;
                                    array_push($val, array("status" =>0,"msm"=>"6.La Recarga supera el monto diario permitido(".number_format((int)$max_rec,0,'',',').")"));
                                  }
                                }
                                else
                                {
                                  $bandera=1;
                                  array_push($val, array("status" =>0,"msm"=>"7.El valor sobrepaso el limite permitido."));
                                }
                              }
                              else// la venta del dia supera el monto maximo permitido
                              {
                                $bandera=1;
                                array_push($val, array("status" =>0,"msm"=>"6.El valor es muy bajo."));
                              }
                            }
                            else
                            {
                              $bandera=1;
                              array_push($val, array("status" =>0,"msm"=>"5.Hubo un problema al validar el tope"));
                            }
                            //VALIDAR EL MONTO MININO Y MAXIMO
                          }
                          else
                          {
                            $bandera=1;
                            array_push($val, array("status" =>0,"msm"=>"4.Su Punto de venta no esta autorizado para BETPLAY."));
                          }
                        }
                        else
                        {
                          $bandera=1;
                          array_push($val, array("status" =>0,"msm"=>"3.No se pudo validar la autorizacion Intenta nuevamente."));
                        }
                      }
                      else
                      {
                        $bandera=1;
                        array_push($val, array("status" =>0,"msm"=>"1.Debe revisar su contrato o esta bloqueado.Contacte a soporte"));
                      }
                    }
                    else
                    {
                      $bandera=1;
                      array_push($val, array("status" =>0,"msm"=>"2.Debe revisar su contrato o esta bloqueado.Contacte a soporte"));
                    }

                    if($bandera==0)
                    {
                      //$cnn_app= new conn(0,0);//conexion bd app
                      $sql="select u.id from users u where u.mail='".$nickname."' and u.estado='A'";
                      $res_usu=$this->cnn->query('19', $sql);//app $cnn_app->Execute($sql);
                      if(count($res_usu)>0)//($res_usu->fields>0)
                      {
                        $con=1;
                        $extras="?con=".$con."&ws_app=1&nickname=".$nickname_cliente."&usuario=".$nickname."&tc=CC&cc=".$cc_apostador."&valor=".$total."&id_tipo=0";
                        $this->log->logs("cem_ws sgc  = ".$url.$extras);
                        $response = $this->http_client->request('GET', $url.$extras);//'https://api.github.com/repos/symfony/symfony-docs');
                        $statusCode = $response->getStatusCode();

                        $resp = null;
                        if($statusCode == "200")
                        {
                          $contentType = $response->getHeaders()['content-type'][0];
                          $resp = $response->getContent();
                        }
                        else
                        {
                          $msm="Error Conexion Sgc: ".$response->getContent();//$e->getMessage();
                          $this->log->logs($msm,$response->getInfo('debug'));
                        }//fin llamada res sgc

                        $this->log->logs("RESPUESTA SGC ".$resp);
                        $dat_ws=explode("|", $resp);
                        if($dat_ws[0]=="1")//respuesta de consulta Exitosa
                        {
                          $con=2;
                          $extras="?con=".$con."&ws_app=1&nickname=".$nickname_cliente."&usuario=".$nickname."&tc=CC&cuenta=".$cc_apostador."&valor=".$total."&pdv=".$pdv_r;
                          ini_set('default_socket_timeout', 500);
                          $response = $this->http_client->request('GET', $url.$extras);//'https://api.github.com/repos/symfony/symfony-docs');
                          $statusCode = $response->getStatusCode();

                          $resp2 = null;
                          if($statusCode == "200")
                          {
                            $contentType = $response->getHeaders()['content-type'][0];
                            $resp2 = $response->getContent();
                          }
                          else
                          {
                            $msm="Error Conexion Sgc: ".$response->getContent();//$e->getMessage();
                            $this->log->logs($msm,$response->getInfo('debug'));
                          }//fin llamada res sgc

                          $this->log->logs("RESPUESTA SGC ".$resp2);
                          $dat_ws2=explode("|",$resp2);

                          if($dat_ws2[0]=="1")//respuesta de consulta Exitosa
                          {
                            $id_r=$dat_ws2[2];//id registro

                            $sql_impri="select c.usuario,
                            (select nombres||' '||apellido1 from personas where documento::text=substring(c.usuario,3)) as nm_vendedor,
                            c.pdv,t.nombre,c.id_apostador,c.valor,cast(c.fecha_recaudado as date) as fecha_r,
                            cast(c.fecha_recaudado as time) as hora,c.pin,c.pin_betplay ,
                            CASE WHEN r.tipo =0 THEN 'Recargas Betplay' WHEN r.tipo =1 THEN 'Retiros Betplay' WHEN r.tipo =2
                            THEN 'Solicitud Retiro Pin' WHEN r.tipo =3 THEN 'Apuestas Rapidas' ELSE 'Producto desconocido' END as tipo_pro,
                            r.tipo,c.nro_factura
                            from cem_regis_detalle r,cem_recaudado c ,territorios t
                            where r.id=c.id_deta_factura::integer
                            and c.estado='0'
                            and t.codigo=c.pdv::integer
                            and r.tipo='0'
                            and c.id='".$id_r."'";

                            $sql1="select nro_resolucion, fecha_resolucion, num_ini, num_fin, prefijo
                            from bemovil_resolucion_dian
                            where fecha_ini<='".date('Y-m-d')."' and (fecha_fin>='".date('Y-m-d')."' or fecha_fin is null)  ";

                            $res1=$this->cnn->query('0', $sql1);//sgc $cnn->Execute($sql1);
                            //echo $sql1;
                            if(count($res1)>0)//($res1->RecordCount()!=0)
                            {
                              $resu_dian=$res1[0]['nro_resolucion'];//$res1->fields[0];
                              $fecha_dian=$res1[0]['fecha_resolucion'];//$res1->fields[1];
                              $num_i=$res1[0]['num_ini'];//$res1->fields[2];
                              $num_f=$res1[0]['num_fin'];//$res1->fields[3];
                              $prefi=$res1[0]['prefijo'];//$res1->fields[4];
                            }
                            $result= $this->cnn->query('0', $sql_impri);//sgc $cnn->Execute($sql_impri);

                            if(count($result)>0)//($result->fields>0)
                            {
                              $nombre=$result[0]['nm_vendedor'];//$result->fields[1];
                              $valor=$result[0]['valor'];//$result->fields[5];
                              $fecha_rel=$result[0]['fecha_r'];//$result->fields[6];
                              $fecha_r=$result[0]['fecha_r']." Hora :".$result[0]['hora'];;//$result->fields[6]." Hora :".$result->fields[7];
                              $recaudador=$result[0]['usuario'];//$result->fields[0];
                              $pventa_u=$result[0]['nombre'];//$result->fields[3];
                              $pin=$result[0]['pin'];//$result->fields[8];
                              $celu=$result[0]['id_apostador'];//$result->fields[4];
                              $name_plan=$result[0]['tipo_pro'];//$result->fields[10];
                              $cc=explode("CV", $recaudador);
                              $nro_fact=$result[0]['nro_factura'];//$result->fields[12];

                            }
                            else
                            {
                              $valor=$total;
                              $celu=$cc_apostador;//$telefo;
                              $name_plan="Recargas Betplay";
                            }
                            array_push($val, array("status" =>1,"id_pago"=>$id_r,"nombre"=>$nombre,"total" =>number_format($valor,0,'.',','),"fecha_rel" =>$fecha_rel,"fechar" =>$fecha_r,"pventa_u" =>$pventa_u,"pin" =>$pin,"recaudador" =>$cc[1],"celu"=>$celu,"name_plan"=>$name_plan,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi,"nro_fact"=>$nro_fact));
                          }
                          else
                          {
                            $msm="";
                            if(count($dat_ws2)>1)
                            {
                              // $msm="".$dat_ws2[0]."-".substr($dat_ws2[1], 55, 20);
                              $msm="".$dat_ws2[0]."-".$dat_ws2[1];
                            }
                            else $msm=$dat_ws2;
                            array_push($val, array("status" =>0,"msm"=>$msm));
                          }
                        }
                        else
                        {
                          $msm="";
                          if(count($dat_ws)>1)
                          {
                            /* $message = trim(substr($dat_ws[1], 55, 20));
                            if(strlen($message)>0)
                              $msm="".$dat_ws[0]."-".$message;
                            else */
                            $msm="".$dat_ws[0]."-".$dat_ws[1];
                          }
                          else $msm=$dat_ws[0];
                          array_push($val, array("status" =>0,"msm"=>$msm));
                        }
                      }
                      else
                      {
                        array_push($val, array("status" =>0,"msm"=>"LO SENTIMOS!, Su usuario esta inactivo o no Existe."));
                      }
                    }
                  }
                  else
                  {
                    array_push($val, array("status" =>0,"msm"=>"LO SENTIMOS!, Su usuario esta inactivo o no Existe."));
                  }
                }
                else
                {
                  array_push($val, array("status" =>0,"msm"=>"Uy, no te alcanza. Intenta Recargar mas saldo."));
                }
              }
              else
              {
                array_push($val, array("status" =>0 ,"msm"=>"Su tipo de usuario aun no tiene permitido Recargas Betplay"));
              }
              $this->log->logs("RESPUESTA: ", array($val));
              $this->log->logs("****************FIN RECARGA BETPLAY*************");
            }
            else if($con=="25")//retiros bet play
            {
              $this->log->logs("****************INICIA RETIRO BETPLAY*************");
              $datos=explode("|", $array);
              $nickname=$datos[0];
              $cc_apostador=$datos[1];
              $total=$datos[2];
              $producto=$datos[3];
              $user_tipo=$datos[4];
              $imei=$datos[5];
              $pinretiro=$datos[6];

              $carpeta=$_ENV['APP_ENV'];//basename(getcwd());
              $bandera=0;
              if($carpeta=="prod")//"ws_app")
              {
                $url="http://10.1.1.4/consuerteinventarios/cem_ws.php";
              }
              else//es pruebas
              {
                // $url="http://10.1.1.12/consuertepruebas/cem_ws.php";
                $url=$this->ip."/consuertepruebas/cem_ws.php";
              }

              if($user_tipo=="2")//si es nivel vendedora
              {
                //$cnn= new conn(0,1);//conexion sgc
                //$cnn2= new conn(2,2);//conexion gamble
                $sql1="SELECT  distinct c.hraprs_ubcneg_trtrio_codigo as punto,c.ubcntrtrio_codigo_compuesto_de as ccostos,c.ubcntrtrio_codigo_compuesto__1 as zona
                from controlhorariopersonas c,contratosventa c2,usuarios c3
                where (c.login,c.cal_dia,c.hhentrada) in( select c4.login,c4.cal_dia,max(c4.hhentrada) as hhentrada from controlhorariopersonas c4,
                (select c3.login, max(c3.cal_dia) as cal_dia from controlhorariopersonas c3 where c3.login='".$nickname."'  group by c3.login ) v
                where c4.login=v.login
                and  c4.cal_dia=v.cal_dia
                group by c4.login,c4.cal_dia) and  c2.login=c.login and c2.fechafinal is null and c3.loginusr=c.login and c3.estado='A'";

                $st=$this->cnn->query('2', $sql1);//gamble 70 $cnn2->Execute($sql1);
                if($st)
                {
                  if(count($st)>0)//($st->fields>0)
                  {
                    //usuario habilitado
                    $pdv_r=$st[0]['PUNTO'];//$st->fields[0];
                    $id_conve_sgc=470;//idtercero de betplay sgc
                    $sql_autoriza="select id_tercero from emsa_asignacion_punto where punto_venta='".$pdv_r."' and id_tercero='".$id_conve_sgc."' and estado='A' ";
                    $res_autoriza=$this->cnn->query('0', $sql_autoriza);//sgc $cnn->Execute($sql_autoriza);
                    if($res_autoriza)
                    {
                      if(count($res_autoriza)>0)//($res_autoriza->fields>0)
                      {
                        if($c_v<0)//si fecha actual es menor a la fecha de control de inicio sistema viejo
                        {
                          $resultados=$this->analizar_bloqueo($nickname,$total,1);
                          $info=explode("|",$resultados);
                          if($info[0]==1)
                          {
                            $sq_control="select id_modulo,min_rec,max_rec,min_ret,max_ret,
                            (select CASE when sum(valor) is null then 0 else sum(valor) end from cem_recaudado
                            where usuario='".$nickname."' and cast(fecha_recaudado as date)='".date('Y-m-d')."' and tipo='1') as vta_actual
                            from users_parametros_recaudos
                            where cast(fecha_ini as date)<='".date('Y-m-d')."' and (cast(fecha_fin as date)>='".date('Y-m-d')."'
                            or fecha_fin is null) and id_modulo='11' and usuario='".$nickname."'";

                            $control=$this->cnn->query('19', $sq_control);//app $cnn_app->Execute($sq_control);
                            if($control)
                            {
                              if(count($control)>0)//($control->fields>0)//hubo resultados el usuario tiene credenciales para
                              {
                                $min_ret=$control[0]['min_ret'];//$control->fields[3];
                                $max_ret=$control[0]['max_ret'];//$control->fields[4];
                                $vta_actual=(int)$control[0]['vta_actual']+(int)$total;

                                if((int)$total>=(int)$min_ret)//si el retiro es mayor o igual al minimo valor de la transaccion asignada al usuario
                                {
                                  if((int)$total<=(int)$max_ret)//si el retiro es menor o igual al maximo valor de la transaccion asignada al usuario
                                  {
                                    if((int)$vta_actual<=(int)$max_ret)//si total(recaudado+transaccion) es menor o igual al tope asignado al usuario
                                    {
                                      //si puede retirar el valor
                                      $this->log->logs("RETIRO BETPLAY: ".$vta_actual."<=".$max_ret);
                                    }
                                    else
                                    {
                                      $bandera=1;
                                      array_push($val, array("status" =>0,"msm"=>"6.El retiro supera el monto diario permitido($".number_format((int)$max_ret,0,'',',').")"));
                                    }
                                  }
                                  else
                                  {
                                    $bandera=1;
                                    array_push($val, array("status" =>0,"msm"=>"7.El valor retiro sobrepaso el limite permitido."));
                                  }
                                }
                                else// la venta del dia supera el monto maximo permitido
                                {
                                  $bandera=1;
                                  array_push($val, array("status" =>0,"msm"=>"6.El valor retiro es muy bajo."));
                                }
                              }
                              else
                              {
                                $bandera=1;
                                array_push($val, array("status" =>0,"msm"=>"8.El usuario no se encuentra parametrizado, comuniquese con su supervisor"));
                              }
                              /*else// defaul
                              {
                                $min_ret=2000;
                                $max_ret=5000000;
                              $sql_vta="select CASE when sum(valor) is null then 0 else sum(valor) end from cem_recaudado where usuario='".$nickname."' and cast(fecha_recaudado as date)='".date('Y-m-d')."' and tipo='1'";
                              $control2=$cnn_app->Execute($sql_vta);
                              $vta_actual=(int)$control2->fields[0]+(int)$total;
                              }*/
                              //$min_ret=$control->fields[3];
                              //$max_ret=$control->fields[4];
                              //validamos monto venta actual no supere el limite
                            }
                            else
                            {
                              $bandera=1;
                              array_push($val, array("status" =>0,"msm"=>"5.Hubo un problema al validar su parametrizacion"));
                            }
                          }
                          else
                          {
                            $bandera=1;
                            array_push($val, array("status" =>0,"msm"=>$info[1]));
                          }
                        }
                        else//inicia sistema nuevo
                        {
                          $res_val_usu = $this->validar_estado_usuario($nickname);
                          $info[0] = $res_val_usu["code"];
                          $info[1] = $res_val_usu["message"];
                          if($info[0]==1)
                          {
                            $sq_control="select id_modulo,min_rec,max_rec,min_ret,max_ret,
                            (select CASE when sum(valor) is null then 0 else sum(valor) end from cem_recaudado
                            where usuario='".$nickname."' and cast(fecha_recaudado as date)='".date('Y-m-d')."' and tipo='1') as vta_actual
                            from users_parametros_recaudos
                            where cast(fecha_ini as date)<='".date('Y-m-d')."' and (cast(fecha_fin as date)>='".date('Y-m-d')."'
                            or fecha_fin is null) and id_modulo='11' and usuario='".$nickname."'";

                            $control=$this->cnn->query('19', $sq_control);//app $cnn_app->Execute($sq_control);
                            if($control)
                            {
                              if(count($control)>0)//($control->fields>0)//hubo resultados el usuario tiene credenciales para
                              {
                                $min_ret=$control[0]['min_ret'];//$control->fields[3];
                                $max_ret=$control[0]['max_ret'];//$control->fields[4];
                                $vta_actual=(int)$control[0]['vta_actual']+(int)$total;

                                if((int)$total>=(int)$min_ret)//si el retiro es mayor o igual al minimo valor de la transaccion asignada al usuario
                                {
                                  if((int)$total<=(int)$max_ret)//si el retiro es menor o igual al maximo valor de la transaccion asignada al usuario
                                  {
                                    if((int)$vta_actual<=(int)$max_ret)//si total(recaudado+transaccion) es menor o igual al tope asignado al usuario
                                    {
                                      //si puede retirar el valor
                                      $this->log->logs("RETIRO BETPLAY: ".$vta_actual."<=".$max_ret);
                                    }
                                    else
                                    {
                                      $bandera=1;
                                      array_push($val, array("status" =>0,"msm"=>"6.El retiro supera el monto diario permitido($".number_format((int)$max_ret,0,'',',').")"));
                                    }
                                  }
                                  else
                                  {
                                    $bandera=1;
                                    array_push($val, array("status" =>0,"msm"=>"7.El valor retiro sobrepaso el limite permitido."));
                                  }
                                }
                                else// la venta del dia supera el monto maximo permitido
                                {
                                  $bandera=1;
                                  array_push($val, array("status" =>0,"msm"=>"6.El valor retiro es muy bajo."));
                                }
                              }
                              else
                              {
                                $bandera=1;
                                array_push($val, array("status" =>0,"msm"=>"8.El usuario no se encuentra parametrizado, comuniquese con su supervisor"));
                              }
                              /*else// defaul
                              {
                                $min_ret=2000;
                                $max_ret=5000000;
                              $sql_vta="select CASE when sum(valor) is null then 0 else sum(valor) end from cem_recaudado where usuario='".$nickname."' and cast(fecha_recaudado as date)='".date('Y-m-d')."' and tipo='1'";
                              $control2=$cnn_app->Execute($sql_vta);
                              $vta_actual=(int)$control2->fields[0]+(int)$total;
                              }*/
                              //$min_ret=$control->fields[3];
                              //$max_ret=$control->fields[4];
                              //validamos monto venta actual no supere el limite
                            }
                            else
                            {
                              $bandera=1;
                              array_push($val, array("status" =>0,"msm"=>"5.Hubo un problema al validar su parametrizacion"));
                            }
                          }
                          else
                          {
                            $bandera=1;
                            array_push($val, array("status" =>0,"msm"=>$info[1]));
                          }
                        }//fin sistema nuevo
                      }
                      else
                      {
                        $bandera=1;
                        array_push($val, array("status" =>0,"msm"=>"4.Su Punto de venta no esta autorizado para BETPLAY."));
                      }
                    }
                    else
                    {
                      $bandera=1;
                      array_push($val, array("status" =>0,"msm"=>"3.No se pudo validar la autorizacion Intenta nuevamente."));
                    }
                  }
                  else
                  {
                    $bandera=1;
                    array_push($val, array("status" =>0,"msm"=>"1.Debe revisar su contrato o esta bloqueado.Contacte a soporte"));
                  }
                }
                else
                {
                  $bandera=1;
                  array_push($val, array("status" =>0,"msm"=>"2.Debe revisar su contrato o esta bloqueado.Contacte a soporte"));
                }
                if($bandera==999)
                {
                  array_push($val, array("status" =>0,"msm"=>"LO SENTIMOS, LA PLATAFORMA ESTA INACTIVA2"));
                }

                if($bandera==0)
                {
                  //$cnn_app= new conn(0,0);//conexion bd app
                  $sql="select u.id from users u where u.mail='".$nickname."' and u.estado='A'";
                  $res_usu=$this->cnn->query('19', $sql);//app $cnn_app->Execute($sql);
                  if(count($res_usu)>0)//($res_usu->fields>0)
                  {
                    $con=1;
                    $extras="?con=".$con."&ws_app=1&nickname=".$nickname."&tc=CC&cc=".$cc_apostador."&valor=".$total."&id_tipo=1";
                    $this->log->logs("cem_ws sgc  = ".$url.$extras);
                    $response = $this->http_client->request('GET', $url.$extras);//'https://api.github.com/repos/symfony/symfony-docs');
                    $statusCode = $response->getStatusCode();

                    $resp = null;
                    if($statusCode == "200")
                    {
                      $contentType = $response->getHeaders()['content-type'][0];
                      $resp = $response->getContent();
                    }
                    else
                    {
                      $msm="Error Conexion Sgc: ".$response->getContent();//$e->getMessage();
                      $this->log->logs($msm,$response->getInfo('debug'));
                    }//fin llamada res sgc

                    $this->log->logs("RESPUESTA SGC BETPLAY2 ".$resp);
                    $dat_ws=explode("|",$resp);
                    if($dat_ws[0]=="1")//respuesta de consulta Exitosa
                    {
                      $con=3;
                      $extras="?con=".$con."&ws_app=1&nickname=".$nickname."&tc=CC&cuenta=".$cc_apostador."&valor=".$total."&pdv=".$pdv_r."&pinretiro=".$pinretiro;
                      $response = $this->http_client->request('GET', $url.$extras);//'https://api.github.com/repos/symfony/symfony-docs');
                      $statusCode = $response->getStatusCode();

                      $resp2 = null;
                      if($statusCode == "200")
                      {
                        $contentType = $response->getHeaders()['content-type'][0];
                        $resp2 = $response->getContent();
                      }
                      else
                      {
                        $msm="Error Conexion Sgc: ".$response->getContent();//$e->getMessage();
                        $this->log->logs($msm,$response->getInfo('debug'));
                      }//fin llamada res sgc

                      $this->log->logs("RESPUESTA SGC BETPLAY2 ".$resp2);
                      $dat_ws2=explode("|",$resp2);

                      if($dat_ws2[0]=="1")//respuesta de consulta Exitosa
                      {
                        $id_r=$dat_ws2[2];//id registro

                        $sql_impri="select c.usuario,
                        (select nombres||' '||apellido1 from personas where documento::text=substring(c.usuario,3)) as nm_vendedor,
                        c.pdv,t.nombre,c.id_apostador,c.valor,cast(c.fecha_recaudado as date) as fecha_r,
                        cast(c.fecha_recaudado as time) as hora,c.pin,c.pin_betplay ,
                        CASE WHEN r.tipo =0 THEN 'Recargas Betplay' WHEN r.tipo =1 THEN 'Retiros Betplay' WHEN r.tipo =2
                        THEN 'Solicitud Retiro Pin' WHEN r.tipo =3 THEN 'Apuestas Rapidas' ELSE 'Producto desconocido' END as tipo_pro,
                        r.tipo,c.nro_factura
                        from cem_regis_detalle r,cem_recaudado c ,territorios t
                        where r.id=c.id_deta_factura::integer
                        and c.estado='0'
                        and t.codigo=c.pdv::integer
                        and r.tipo='1'
                        and c.id='".$id_r."'";

                        $sql1="select nro_resolucion, fecha_resolucion, num_ini, num_fin, prefijo
                        from bemovil_resolucion_dian where fecha_ini<='".date('Y-m-d')."' and (fecha_fin>='".date('Y-m-d')."'
                        or fecha_fin is null)  ";

                        $res1=$this->cnn->query('0', $sql1);//sgc $cnn->Execute($sql1);
                        //echo $sql1;
                        if(count($res1)>0)//($res1->RecordCount()!=0)
                        {
                          $resu_dian=$res1[0]['nro_resolucion'];//$res1->fields[0];
                          $fecha_dian=$res1[0]['fecha_resolucion'];//$res1->fields[1];
                          $num_i=$res1[0]['num_ini'];//$res1->fields[2];
                          $num_f=$res1[0]['num_fin'];//$res1->fields[3];
                          $prefi=$res1[0]['prefijo'];//$res1->fields[4];
                        }
                        $result= $this->cnn->query('0', $sql_impri);//app$cnn->Execute($sql_impri);

                        if(count($result)>0)//($result->fields>0)
                        {
                          $nombre=$result[0]['nm_vendedor'];//$result->fields[1];
                          $valor=$result[0]['valor'];//$result->fields[5];
                          $fecha_rel=$result[0]['fecha_r'];//$result->fields[6];
                          $fecha_r=$result[0]['fecha_r']." Hora :".$result[0]['hora'];//$result->fields[6]." Hora :".$result->fields[7];
                          $recaudador=$result[0]['usuario'];//$result->fields[0];
                          $pventa_u=$result[0]['nombre'];//$result->fields[3];
                          $pin=$result[0]['pin'];//$result->fields[8];
                          $celu=$result[0]['id_apostador'];//$result->fields[4];
                          $name_plan=$result[0]['tipo_pro'];//$result->fields[10];
                          $cc=explode("CV", $recaudador);
                          $nro_fact=$result[0]['nro_factura'];//$result->fields[12];
                        }
                        else
                        {
                          $valor=$total;
                          $celu=$cc_apostador;//$telefo;
                          $name_plan="Retiros Betplay";
                        }
                        array_push($val, array("status" =>1,"id_pago"=>$id_r,"nombre"=>$nombre,"total" =>number_format($valor,0,'.',','),"fecha_rel" =>$fecha_rel,"fechar" =>$fecha_r,"pventa_u" =>$pventa_u,"pin" =>$pin,"recaudador" =>$cc[1],"celu"=>$celu,"name_plan"=>$name_plan,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi,"nro_fact"=>$nro_fact));
                      }
                      else if($dat_ws[0]=="999")
                      {
                        $msm=$dat_ws[1];
                        $arr_msm=explode(",", $msm);
                        if(count($arr_msm)>1) $msm= "Saldo insuficiente, ".$arr_msm[1];
                        if(empty($dat_ws[1])) $msm="Error sin identificar , contacte al administrador del sistema.";
                        array_push($val, array("status" =>0,"msm"=>$msm));
                      }
                      else
                      {
                        $msm="";
                        if(count($dat_ws2)>1)
                        {
                          // $msm="".$dat_ws2[0]."-".substr($dat_ws2[1], 55, 20);
                          $msm="".$dat_ws2[0]."-".$dat_ws2[1];
                        }
                        else $msm=$dat_ws2[0];
                        array_push($val, array("status" =>0,"msm"=>$msm));
                      }
                    }
                    else
                    {
                      $msm="";
                      if(count($dat_ws)>1)
                      {
                        /* $message = trim(substr($dat_ws[1], 55, 20));
                        if(strlen($message)>0)
                          $msm="".$dat_ws[0]."-".$message;
                        else */
                        $msm="".$dat_ws[0]."-".$dat_ws[1];
                      }
                      else $msm=$dat_ws[0];
                      array_push($val, array("status" =>0,"msm"=>$msm));
                    }
                  }
                  else
                  {
                    array_push($val, array("status" =>0,"msm"=>"LO SENTIMOS!, Su usuario esta inactivo o no Existe."));
                  }
                }
              }
              else
              {
                array_push($val, array("status" =>0 ,"msm"=>"Su tipo de usuario aun no tiene permitido Recargas Betplay"));
              }
              $this->log->logs("RESPUESTA: ", array($val));
              $this->log->logs("****************FIN RETIRO BETPLAY*************");
            }
            else if($con=="26")//activa cuenta
            {
              $data=explode("|", $array);
              $token_ws=$data[0];
              $token=$data[1];
              $fecha_exp=$data[2];

              if($token_ws=="b586ef9772f7e23075a393b4ac87eea9")
              {
                //$cnn_app= new conn(0,0);//conexion bd app
                $fecha_actual=date("Y-m-d H:i:s");
                $tiempo_segundos = strtotime($fecha_actual) - strtotime($fecha_exp);
                $status=0;

                if($tiempo_segundos<1800)//si es menos a 30 minutos
                {
                  //proceder a activar cuenta
                  $sql="select mail from users_activacion where token='".$token."' and estado='A'";
                  $res=$this->cnn->query('19', $sql);//app $cnn_app->Execute($sql);
                  if($res)
                  {
                    if(count($res)>0)//($res->fields>0)
                    {
                      $usuario=$res[0]['mail'];//$res->fields[0];
                      $update_usu="update users set estado='A' where mail='".$usuario."' returning id";
                      $upd=$this->cnn->query('19', $update_usu);//app $cnn_app->Execute($udpate_usu);

                      if(!empty($upd))
                      {
                        if(count($upd)>0)//($upd->fields>0)
                        {
                          $sql_update="update users_activacion set estado='I' where token='".$token."'";
                          $this->cnn->query('19', $sql_update);//app $cnn_app->Execute($sql_update);
                          $msm="Cuenta activada correctamente";
                          $status=1;
                        }
                        else
                        {
                          $msm="1.Hubo un error intente otra vez...";
                        }
                      }
                      else
                      {
                        $msm="2.Hubo un error intente otra vez...";
                      }
                    }
                    else
                    {
                      $msm="3.No se encontraron Resultados O Token expirado";
                    }
                  }
                  else
                  {
                    $msm="4.Hubo un error intente otra vez...";
                  }
                }
                else
                {
                  $sql="select mail from users_activacion where token='".$token."' and estado='A'";
                  $res=$this->cnn->query('19', $sql);//app $cnn_app->Execute($sql);
                  if($res)
                  {
                    if(count($res)>0)//($res->fields>0)
                    {
                      $sql_update="update users_activacion set estado='I' where token='".$token."'";
                      $res2=$this->cnn->query('19', $sql_update);//app $cnn_app->Execute($sql_update);
                      if(!empty($res2))
                      {
                        //$status=1;
                      }
                      $msm="Url expiro";
                    }
                    else
                    {
                      $msm="Url Inactiva, debe solicitar un nuevo token";
                    }
                  }
                  else
                  {
                    $msm="No se pudo validar, intente mas tarde";
                  }
                }
                array_push($val, array("status" =>$status,"msm"=>$msm));
              }
              else
              {
                array_push($val, array("status" =>0,"msm"=>"El token no es valido, No se puede activar la cuenta."));
              }
            }
            else if($con=="27")// retorna el listados de los convenios con sus campos //funciona
            {
              if(!$this->doAuthenticate())
              {
                array_push($val, array("status" =>0,'msm' => "Invalido Usuario o Password Webservice"));

                //return "Invalid username or password";
                //$permiso=1;
                //return json_encode($val);
              }
              else
              {
                //$cnn_= new conn(0,1); //conexion SGC pruebas dependiendo de la ubicacion
                $inf_ws=explode("|", $array);
                $tipouser=$inf_ws[0];//= tipo usuario
                $ver_ini=$inf_ws[1];//= ver_ini
                $id_mod=$inf_ws[2];//id_modulo
                $this->log->logs("Obteniendo listado convenios ".$inf_ws[0].",".$inf_ws[1]);
                if($tipouser=="5") //(count($inf_ws)>1)
                {
                  if($id_mod=="5")//recaudo convenios
                  {
                    $sql_con="SELECT m.id,m.siglas,m.razon,m.nit_real as nit,m.id_tercero,
                    case when t.codigo_empresa is null then 1 else 0 end as lector_barras,
                    t.esp,t.campo_etiqueta,t.campo_tipo,t.campo_valor,t.campos_mostrar,'3:consulta|4:recaudo' as con
                    from consuerte_pay.mod_convenios m, tablas_recaudos_facturas t
                    where m.estado_maquina='0' and m.ver_ini<='".$ver_ini."' and m.id_tercero=t.id_tercero::text
                    and t.grupal='0' and t.estado='0' and t.especial='0' and m.estado='0' ";
                  }
                  else if($id_mod=="11")//betplay
                  {
                    $sql_con="SELECT m.id,'Recarga Betplay' as siglas,i.razon,i.nit,t.id_tercero,
                    case when t.codigo_empresa is null then 1 else 0 end as lector_barras,
                    t.esp,t.campo_etiqueta,t.campo_tipo,t.campo_valor,t.campos_mostrar,'24:recarga' as con
                    from consuerte_pay.app_modulos m, tablas_recaudos_facturas t , terceros_inv i
                    where m.estado='0' and m.id='11' and t.id_tercero='470' and t.id_tercero=i.id
                    and t.start='0' and t.estado='0' and t.especial='0' ";
                  }
                  $r=$this->cnn->query('0', $sql_con);//sgc $cnn_->Execute($sql_con);
                  //$cnn_->close();

                  if($r)
                  {
                    if(count($r)>0)//($r->fields>0)
                    {
                      $dat=array();
                      /*$paq=array();
                      $pin=array();
                      $run=array(); */
                      //while (!$r->EOF)
                      foreach ($r as $key => $row)
                      {
                        //$vista_valor=number_format($r->fields[4],0,'.',',');

                        //array_push($dat, array("siglas"=>utf8_encode($r->fields[1]),"razon"=>utf8_encode($r->fields[2]),"nit"=>$r->fields[3],"id_tercero"=>$r->fields[4],"lector"=>$r->fields[5],"esp"=>$r->fields[6],"etiquetas"=>utf8_encode($r->fields[7]),"tipos"=>utf8_encode($r->fields[8]),"valores"=>utf8_encode($r->fields[9]),"campos_mostrar"=>utf8_encode($r->fields[10]),"con"=>utf8_encode($r->fields[11])));
                        //array_push($dat, array("siglas"=>utf8_encode($row['siglas']),"razon"=>utf8_encode($row['razon']),"nit"=>$row['nit'],"id_tercero"=>$row['id_tercero'],"lector"=>$row['lector_barras'],"esp"=>$row['esp'],"etiquetas"=>utf8_encode($row['campo_etiqueta']),"tipos"=>utf8_encode($row['campo_tipo']),"valores"=>utf8_encode($row['campo_valor']),"campos_mostrar"=>utf8_encode($row['campos_mostrar']),"con"=>utf8_encode($row['con'])));
                        array_push($dat, array("siglas"=>mb_convert_encoding($row['siglas'],'utf8'),"razon"=>mb_convert_encoding($row['razon'],'utf8'),"nit"=>$row['nit'],"id_tercero"=>$row['id_tercero'],"lector"=>$row['lector_barras'],"esp"=>$row['esp'],"etiquetas"=>mb_convert_encoding($row['campo_etiqueta'],'utf8'),"tipos"=>mb_convert_encoding($row['campo_tipo'],'utf8'),"valores"=>mb_convert_encoding($row['campo_valor'],'utf8'),"campos_mostrar"=>mb_convert_encoding($row['campos_mostrar'],'utf8'),"con"=>mb_convert_encoding($row['con'],'utf8')));

                        //array_push($val, array("id_producto"=>$r->fields[0],"id_paq"=>$r->fields[1],"id_ope"=>$r->fields[2],"descripcion"=>$r->fields[3],"valor"=>(int)$r->fields[4],"desc_ope"=>$r->fields[5],"tipo_paq"=>$r->fields[6],"tipo_pro"=>$r->fields[7],"tipo"=>$r->fields[8],"vista_total"=>"$ ".$vista_valor));
                        //print($dat[0]]);
                        //$r->moveNext();
                      }
                      array_push($val, array("status" =>1,"convenios"=>$dat));
                    }
                    else
                    {
                      array_push($val, array("status" =>0,"msm"=>"No Se encontraron Convenios."));
                    }
                  }
                  else
                  {
                    array_push($val, array("status" =>0,"msm"=>"Error al Consultar los Convenios Disponibles."));
                  }
                }
                else
                {
                  array_push($val, array("status" =>0,"msm"=>"Su Tipo de Usuario No Tiene Permisos."));
                }
              }//fin user y pass webservice
            }//fin con==27
            else if($con=="28")// retorna el listados modulos //funciona
            {
              if(!$this->doAuthenticate())
              {
                array_push($val, array("status" =>0,'msm' => "Invalido Usuario o Password Webservice"));

                //return "Invalid username or password";
                //$permiso=1;
                //return json_encode($val);
              }
              else
              {
                //$cnn_= new conn(0,1); //conexion SGC pruebas dependiendo de la ubicacion
                $inf_ws=explode("|", $array);
                $tipouser=$inf_ws[0];//= tipo usuario
                //$ver_ini=$inf_ws[1];//= ver_ini
                $this->log->logs("Obteniendo listado Modulos ".$inf_ws[0]); //.",".$inf_ws[1]);
                if($tipouser=="5") //(count($inf_ws)>1)
                {
                  $sql_con="SELECT m.id,m.descripcion as tipo, m.nom_alternativo
                  from consuerte_pay.app_modulos m
                  where m.permisos ilike '%,".$tipouser.",%' and m.estado='0'";
                  $r=$this->cnn->query('0', $sql_con);//sgc $cnn_->Execute($sql_con);
                  //$cnn_->close();

                  if($r)
                  {
                    if(count($r)>0)//($r->fields>0)
                    {
                      $dat=array();
                      /*$paq=array();
                      $pin=array();
                      $run=array(); */
                      //while (!$r->EOF)
                      foreach ($r as $key => $row)
                      {
                        //$vista_valor=number_format($r->fields[4],0,'.',',');

                        //array_push($dat, array("id"=>utf8_encode($r->fields[0]),"tipo"=>utf8_encode($r->fields[1]),"nombre"=>utf8_encode($r->fields[2])));
                        //array_push($dat, array("id"=>utf8_encode($row['id']),"tipo"=>utf8_encode($row['tipo']),"nombre"=>utf8_encode($row['nom_alternativo'])));
                        array_push($dat, array("id"=>mb_convert_encoding($row['id'],'utf8'),"tipo"=>mb_convert_encoding($row['tipo'],'utf8'),"nombre"=>mb_convert_encoding($row['nom_alternativo'],'utf8')));

                        //array_push($val, array("id_producto"=>$r->fields[0],"id_paq"=>$r->fields[1],"id_ope"=>$r->fields[2],"descripcion"=>$r->fields[3],"valor"=>(int)$r->fields[4],"desc_ope"=>$r->fields[5],"tipo_paq"=>$r->fields[6],"tipo_pro"=>$r->fields[7],"tipo"=>$r->fields[8],"vista_total"=>"$ ".$vista_valor));
                        //print($dat[0]]);
                        //$r->moveNext();
                      }
                      array_push($val, array("status" =>1,"modulos"=>$dat));
                    }
                    else
                    {
                      array_push($val, array("status" =>0,"msm"=>"No Se Encontraron Modulos."));
                    }
                  }
                  else
                  {
                    array_push($val, array("status" =>0,"msm"=>"Error al Consultar los Modulos Disponibles."));
                  }
                }
                else
                {
                  array_push($val, array("status" =>0,"msm"=>"Su Tipo de Usuario No Tiene Permisos."));
                }
              }//fin user y pass webservice
            }//fin con==28 consulta de modulos
            else if($con=="29")//get regimen y tipo de identificacion
            {
              $this->log->logs("*****************INICIA PROCESO GET REGIMEN Y TIPO IDENTIFICACION****************");
              $datos=explode("|", $array);
              $nickname=$datos[0];//nickanme vendedora o usuario
              if($nickname!="")
              {
                $carpeta = $_ENV['APP_ENV'];
                $contenido = array();
                try
                {
                  if ($carpeta == "prod") $url = "http://10.1.1.4:8094/listaclientes"; else $url = "http://localhost:8094/listaclientes";

                  $httpClient = HttpClient::create();
                  $parametro1 = '[{"con":"71"}]';
                  $parametro2 = '{"nickname":"'.$nickname.'"}';
                  $response_sgc = $httpClient->request('POST', $url,
                  [
                    'body' =>(['json' => $parametro1,]),
                    'headers' =>['Content-Type' => 'application/json','AUTHENTICATION' => $parametro2,],
                  ]);
                  $respuesta = $response_sgc->getContent();
                  $data = json_decode($respuesta);
                  foreach ($data as $item)
                    if($item->status==1 && $item->code==200)
                      $contenido[0] = $item->datos;

                  $parametro1 = '[{"con":"72"}]';
                  $response_sgc = $httpClient->request('POST', $url,
                  [
                    'body' =>(['json' => $parametro1,]),
                    'headers' =>['Content-Type' => 'application/json','AUTHENTICATION' => $parametro2,],
                  ]);
                  $respuesta = $response_sgc->getContent();
                  $data = json_decode($respuesta);
                  foreach ($data as $item)
                    if($item->status==1 && $item->code==200)
                      $contenido[1] = $item->datos;

                  array_push($val, array("status" =>1,"msm"=>"OK", "datos"=>$contenido));
                }
                catch (\Exception $e)
                {
                  $this->log->logs("ERROR file_get_contents " . $e);
                  array_push($val, array("status" =>0,"msm"=>"Error al obtener los datos: $e"));
                }
              }
              else
              {
                array_push($val, array("status" =>0,"msm"=>"Su sesion ha Expirado, Vuelva a ingresar"));
              }
              $this->log->logs("RESPUESTA: ", array($val));
              $this->log->logs("*****************FIN PROCESO GET REGIMEN Y TIPO IDENTIFICACION****************");
            }//fin con==29 consulta regimen y tipo de identificacion
            else if($con=="30")//valida usuario factura electronica
            {
              $this->log->logs("*****************INICIA PROCESO VALIDA USUARIO FACTURA ELECTRONICA****************");
              $datos=explode("|", $array);
              $nickname=$datos[0];//nickanme vendedora o usuario
              $ced_cli=$datos[1];//cedula del cliente
              $tip_doc=$datos[2];//tipo identificacion
              if($nickname!="")
              {
                $carpeta = $_ENV['APP_ENV'];
                $contenido = "";
                try
                {
                  if ($carpeta == "prod") $url = "http://10.1.1.4:8094/datos21"; else $url = "http://localhost:8094/datos21";

                  $httpClient = HttpClient::create();
                  $parametro1 = '[{"con":"19", "ced_cli":"'.$ced_cli.'", "tipo":"0", "tip_doc":"'.$tip_doc.'"}]';
                  $parametro2 = '{"nickname":"'.$nickname.'"}';
                  $response_sgc = $httpClient->request('POST', $url,
                  [
                    'body' =>(['json' => $parametro1,]),
                    'headers' =>['Content-Type' => 'application/json','AUTHENTICATION' => $parametro2,],
                  ]);
                  $respuesta = $response_sgc->getContent();
                  $data = json_decode($respuesta);
                  foreach ($data as $item)
                    if($item->status==1 && $item->code==200)
                      $contenido = $item->datos;

                  array_push($val, array("status" =>1,"msm"=>"OK", "datos"=>$contenido));
                }
                catch (\Exception $e)
                {
                  $this->log->logs("ERROR file_get_contents " . $e);
                  array_push($val, array("status" =>0,"msm"=>"Error al obtener los datos: $e"));
                }
              }
              else
              {
                array_push($val, array("status" =>0,"msm"=>"Su sesion ha Expirado, Vuelva a ingresar"));
              }
              $this->log->logs("RESPUESTA: ", array($val));
              $this->log->logs("*****************FIN PROCESO VALIDA USUARIO FACTURA ELECTRONICA****************");
            }//fin con==30 valida usuario factura electronica
          }
          else
          {
            array_push($val, array("status" =>'-1',"msm"=>"Servicios en mantenimiento, intente mas tarde..."));
          }
          if(empty($val))
          {
            array_push($val, array("status" =>0,"msm"=>"Error Desconocido, Comuniquese de inmediato con Soporte"));
          }
        }
        else
        {
          $valor="1|Alguno de los Datos se Encuentra Vacio.|";
          array_push($val, array("status" =>1,"valor" => $valor,"msm"=>"Alguno de los Datos se Encuentra Vacio.","code"=>200));
        }
      }
      else
      {
        $valor="1|No Llegaron los Datos al Sgc.|";
        array_push($val, array("status" =>1,"valor" => $valor,"msm"=>"No Llegaron los Datos al Sgc.","code"=>200));
      }
      $this->log->logs("********************Termina Metodos***********************");
      return new JsonResponse($val);
    }

    #[Route('/app/metodos_web', name: 'app_app_metodos_web', methods: ['POST'])]
    public function metodos_web(Request $request)//$con,$array,$token)
    {
      $this->log->logs("********************Inicia Metodos Web***********************");
      //recoger los datos por post
      $json = $request->get('json', null);

      /* var_dump($json);
      die(); */
      // decodigficar el json
      $params = json_decode($json);//,true);

      /* var_dump($params);//[0]->id_user);
      die();  */
      //respuesta por defecto
      $val=array();
      /* $data =[
        'status' => 'error',
        'code'  => 200,
        'message' => 'Prueba Error Datos',
        'params' => $params
      ];    */
      //array_push($val, array("status" =>1,"msm"=>"Prueba Error Datos","code"=>'200'));

      //$cnn->logs('CP86069529','pruebas');

      /*   var_dump($params);
      die();  */

      //comprobar y validar datos
      if($json != null)
      {
        $this->log->logs('Se Reciben los Datos:',$params);

        $con = (!empty($params[0]->con)) ? $params[0]->con  : null;
        $array = (!empty($params[0]->array)) ? $params[0]->array  : null;
        $token = (!empty($params[0]->token)) ? $params[0]->token  : null;
        $this->php_auth_user = (!empty($params[0]->php_auth_user)) ? $params[0]->php_auth_user  : null;
        $this->php_auth_pw = (!empty($params[0]->php_auth_pw)) ? $params[0]->php_auth_pw  : null;

        if(!empty($con)  && !empty($array) && !empty($array) )//&& !empty($tipo_r))
        {
          $val=array();
          global $GLOBALS;
          $status=0;
          if($token=="b586ef9772f7e23075a393b4ac87eea9")//token universal
          {
            if($con=="1")//validar tirilla numero factura formulario
            {
              $status=0;
              $msm="";
              //array_push($val, array("status" => 1,"msm"=>"LLego correctamente"));
              $nro_fact=strtoupper($array);
              $seri=substr($nro_fact, 0,3);
              $nrm_frm=substr($nro_fact,3);
              $sql_gamble="SELECT FECHAVENTA,HORAVENTA FROM DETALLEVTASOTROSPRODUCTOS
              WHERE FECHAVENTA between to_date( '2021-06-10', 'YYYY-MM-DD') and to_date('2021-07-10', 'YYYY-MM-DD')
              AND serie='$seri' AND NUMERO='$nrm_frm' and SERVICIO_CODIGO in('699') and valor>=10000";
              //$cnn2_= new conn(2,2);//conexion oracle a 70
              $rest=$this->cnn->query('2', $sql_gamble);//gamble 70 $cnn2_->Execute($sql_gamble);
              //validando gamble
              if($rest)
              {
                if(count($rest)>0)//($rest->recordcount()!=0)
                {
                  $status=1;
                  $msm="1.Registro encontrado Correctamente,Gracias por registrarse, muy pronto recibiras todas nuestras promociones";
                }
                else
                {
                  $status=0;//se deja en
                  $msm="No hay resultados de la busqueda por el NRO.FACTURA";
                }
              }
              else
              {
                $status=0;
                $msm="Hubo un problema con el servicio intente nuevamente.";
              }
            }
            else
            {
              $msm="No existe el metodo.";
              //array_push($val, array("status" => 0,"msm"=>"No existe el metodo"));
            }
          }
          else
          {
            $msm="El token es invalido.";
            //array_push($val, array("status" => 0,"msm"=>"LLego correctamente"));
          }

        }
        else
        {
          array_push($val, array("status" =>0,"msm"=>"Alguno de los Datos se Encuentra Vacio.","code"=>200));
        }
      }
      else
      {
        array_push($val, array("status" =>0,"msm"=>"No Llegaron los Datos al Sgc.","code"=>200));
      }
      array_push($val, array("status" => $status,"msm"=>$msm));
      $this->log->logs("JSON A RETORNAR :".$val);
      $this->log->logs("********************Termina Metodos Web***********************");
      return new JsonResponse($val);
    }

    #[Route('/app/validar_maquina', name: 'app_app_validar_maquina', methods: ['POST'])]
    public function validar_maquina(Request $request)
    {
      $this->log->logs("********************Inicia VALIDAR MAQUINA***********************");
      //recoger los datos por post
      $json = $request->get('json', null);
      $params = json_decode($json);//,true);
      $val=array();

      //comprobar y validar datos
      if($json != null)
      {
        $this->log->logs('Se Reciben los Datos:',$params);

        $user = (!empty($params[0]->user)) ? $params[0]->user  : null;
        $pass = (!empty($params[0]->pass)) ? $params[0]->pass  : null;
        $datos = (!empty($params[0]->datos)) ? $params[0]->datos  : null;
        $this->php_auth_user = (!empty($params[0]->php_auth_user)) ? $params[0]->php_auth_user  : null;
        $this->php_auth_pw = (!empty($params[0]->php_auth_pw)) ? $params[0]->php_auth_pw  : null;

        $this->log = new Log('app_sgc',$user,$this->ruta);

        if(!empty($user)  && !empty($pass)  && !empty($datos) )//&& !empty($tipo_r))
        {
          if(!$this->doAuthenticate())
          {
            array_push($val, array('Error' => "Invalido Usuario o Password"));
            $this->log->logs("Error Usuario o Password Webservice Invalido.");
          }
          else
          {
            $pass=md5(trim($pass));
            $inf_device=explode("|", $datos);
            //$token= $inf_device[0];
            //$imeis=$inf_device[1];
            $ips=$inf_device[0];//ip
            $mac=$inf_device[1];//mac de la maquina
            $name_device=$inf_device[2];//nombre deldispositivo
            $sqmant="SELECT * from datos where id='2' and estado='1'";
            //$cnn_app_->conectar();
            $res_man=$this->cnn->query('19', $sqmant);//app  $cnn_app_->Execute($sqmant);
            //$cnn_app_->close();
 	          //$val=array();
            if(empty($res_man))
            {
              if(count($res_man)==0) //($res_man->fields==0)
              {
	              $r=0;// 0=false 1=true
	              $msm_login="";
                $id_huella="0";
                $sql="SELECT u.id,u.cc,u.nombre,u.mail,u.login,(select string_agg(cast(s.id as text), ',') from (
                select id,excluir from app_modulos where estado='0' and permisos like '%,'||u.tipo_user||',%' )s
                where s.excluir not like '%,'||u.mail||',%' or s.excluir is null )as modules,
                (select firebase from firebaseid where id_users=u.mail  order by fecha_sys desc limit 1) as token,
                u.tipo_user,u.apellido,u.tel,(select string_agg(cast(id as text), ',')
                from parametros_pagos where estado='0' )as pasarelas_pagos,u.fecha_nac
                from users u where u.mail='".$user."' and u.pass='".$pass."' and u.estado='A' ";// and u.tipo_user='5'";
	              $res=$res_man=$this->cnn->query('19', $sql);//app $cnn_->Execute($sql);
	              $val_status_gamble="A";
	              if(count($res)>0)//($res->fields>0)
	              {
                  if($res[0]['tipo_user']=="5" || $res[0]['tipo_user']=="6")//($res->fields[7]=="5")
                  {
                    $sql_mac="SELECT (select estado from usuarios where loginusr='".$user."') as estado_user, m2.DIRECCION_MAC_EQUIPO ,m2.puntoventa,m2.fechasys
                    from MAC_PUNTO_VENTA m2 inner join
                    (select m.puntoventa,max(m.fechasys) as fecha from MAC_PUNTO_VENTA m where m.PUNTOVENTA=(SELECT  distinct c.hraprs_ubcneg_trtrio_codigo as punto
                    from controlhorariopersonas c,contratosventa c2
                    where (c.login,c.cal_dia,c.hhentrada) in( select c4.login,c4.cal_dia,max(c4.hhentrada) as hhentrada
                    from controlhorariopersonas c4,
                    (select c3.login, max(c3.cal_dia) as cal_dia from controlhorariopersonas c3 where c3.login='".$user."'  group by c3.login ) v
                    where c4.login=v.login
                    and  c4.cal_dia=v.cal_dia
                    group by c4.login,c4.cal_dia) and  c2.login=c.login and c2.fechafinal is null) and m.estado='A' group by m.puntoventa) b
                    on m2.puntoventa=b.puntoventa and m2.fechasys=b.fecha ";
                    $vs=$this->cnn->query('2', $sql_mac);//gamble $cnn2->Execute($sql_mac);
                    $succes=true;
                    if($vs)
                    {
                      if(count($vs)>0)//($vs->fields>0)
                      {
                        $val_status_gamble=$vs[0]['ESTADO_USER'];//$vs->fields[0];
                        $mac_gamble=trim($vs[0]['DIRECCION_MAC_EQUIPO']);//$vs->fields[1]);
                        $this->log->logs("Comparando Macs: ".$mac." | ".$mac_gamble);
                        $pos = strpos($mac,$mac_gamble);

                        if($pos===false)//no se encontro coincidencias de macs
                        {
                          $succes=false;
                          $msm_login="La mac o Imei no coincide, comuniquese con soporte.";
                        }
                      }
                      else//NO ESTA ACTIVO O NO EXISTE
                      {
                        $val_status_gamble="";
                        $succes=false;
                        $msm_login="No se encontro Usuario en gamble.";
                      }
                    }
                    else//NO EXISTE
                    {
                      $val_status_gamble="";
                      $succes=false;
                      $msm_login="Ocurrio un problema al validar el estado, intente nuevamente.";
                    }

                    if($succes)
                    {

                      $sql_valida_sesion="select id from users_sesion where id_user='".$res[0]['id']."' and date(fechai)='".date('Y-m-d')."'  and fechaf is null";
                      $res_vs=$this->cnn->query('19', $sql_valida_sesion);//app $cnn_->Execute($sql_update);

                      if(!count($res_vs)>0) //no tiene una sesion activa el dia actual en consuerte pay
                      {
                        $sql_update="update users_sesion set fechaf='now()' where id_user='".$res[0]['id']."' and fechaf is null";
                        $sesion=md5($pass.$res[0]['mail'].date('Y-m-d H:i:s'));//passwordcorreofecha
                        $sql_sesion="insert into users_sesion (id_user,token,fechai,ips,name_device,imeis)
                        values('".$res[0]['id']."','".$sesion."','now()','".$ips."','".$name_device."','".$mac."');";

                        $this->cnn->query('19', $sql_update);//app $cnn_->Execute($sql_update);
                        $this->cnn->query('19', $sql_sesion);//app $cnn_->Execute($sql_sesion);
                      }
                      array_push($val, array("status" => 1,"tipo"=>$res[0]['tipo_user']));
                    }
                    else
                    {
                      array_push($val, array("status" => 0,"msm"=>$msm_login,"bloqueo_g"=>$val_status_gamble));
                    }
                  }
                  else
                  {
                    array_push($val, array("status" => 1,"msm"=>"Error el Usuario no Tiene Permisos para Validarse.","bloqueo_g"=>$val_status_gamble));
                  }
	              }
	              else
	              {
	                array_push($val, array("status" => 0,"msm"=>"Error no existe el usuario o pass incorrecto","bloqueo_g"=>$val_status_gamble));
	              }
              }
              else
              {
           	    array_push($val, array("status" =>0,"msm"=>"Servicios en mantenimiento, intente mas tarde...","bloqueo_g"=>"A"));
              }
            }
            else
            {
              array_push($val, array("status" =>0,"msm"=>"No se pudo validar el servicio, intente nuevamente.","bloqueo_g"=>"A"));
            }
          }
        }
        else
        {
          $valor="1|Alguno de los Datos se Encuentra Vacio.|";
          array_push($val, array("status" =>1,"valor" => $valor,"msm"=>"Alguno de los Datos se Encuentra Vacio.","code"=>200));
        }
      }
      else
      {
        $valor="1|No Llegaron los Datos al Sgc.|";
        array_push($val, array("status" =>1,"valor" => $valor,"msm"=>"No Llegaron los Datos al Sgc.","code"=>200));
      }
      $this->log->logs("********************Termina Validar Maquina***********************");
      return new JsonResponse($val);
    }

    #[Route('/app/test_registro', name: 'app_app_test_registro', methods: ['POST'])]
    public function test_registro(Request $request)
    {
    }

    //#[Route('/app/verificacion_transaccion_bemovil', name: 'app_app_verificacion_transaccion_bemovil', methods: ['POST'])]
    public function verificacion_transaccion(Request $request)
    {
      $this->log->logs("********************Inicia Verificacion Transaccion ***********************");

      $json = $request->get('json', null);
      $params = json_decode($json);//,true);
      $val=array();

      if($json != null)
      {
        $this->log->logs('Se Reciben los Datos:',$params);
        $con = (!empty($params[0]->con)) ? $params[0]->con  : null;
        $array = (!empty($params[0]->array)) ? $params[0]->array  : null;
        $VERSION_CODE = (!empty($params[0]->VERSION_CODE)) ? $params[0]->VERSION_CODE  : null;
        $this->php_auth_user = (!empty($params[0]->php_auth_user)) ? $params[0]->php_auth_user  : null;
        $this->php_auth_pw = (!empty($params[0]->php_auth_pw)) ? $params[0]->php_auth_pw  : null;

        if($con=="4") //convenios Emsa-Edesa-Llanogas-Acueducto
        {
          $this->log->logs("********************Inicia Verificacion Transaccion Recaudos Emsa-Especial-Llanogas-Acueducto***********************");
          $dt = explode("|",$array);
          $this->log = new Log('app_sgc',$dt[0],$this->ruta);

          $datos=explode("|", $array);
          $nickname=$datos[0]; //vendedor
          $tercero=$datos[1]; // tercero
          $facturaid=$datos[2]; // id regis detalle
          $valorre=$datos[3]; //valor total factura
          $fecha_re_hh=$datos[4]; // fecha y hora de peticion al momento de enviar primera solicitud de recaudo
          $fecha_explo=(explode(" ",$fecha_re_hh)); // [0] =fecha  [1]=hora
          $valor_redondeado=$datos[5];
          $valor_llanogas_homo=$valorre;
          if($tercero=="129") //llanogas consulta si tiene homologado
          {
            $this->log->logs("ID REGIS LLANOGAS  ".$facturaid);
            $id_llanogas_homo="'".$facturaid."'";
            $operador_sql="=";

            $sql_homo="select id_homologa, valor_serviciop from llanogas_regis_detalle_ws where id=$facturaid ";
            $homol=$this->cnn->query('0', $sql_homo);//app $cnn->Execute($sql_homo);
            if($homol)
            {
              if(count($homol)>0)//($homol->fields>0)
              {
                $id_homo=$homol[0]['id_homologa'];//$homol->fields[0];
                $valor_llanogas=$homol[0]['valor_serviciop'];

                $this->log->logs("VALOR llanogas:".$valor_llanogas." , id_llanogas:".$id_homo);
                if($id_homo!="" && $id_homo!="0" && $id_homo!="null")
                {
                  $sql_homo2="select id_homologa, valor_serviciop from llanogas_regis_detalle_ws where id=$id_homo ";
                  $homol2=$this->cnn->query('0', $sql_homo2);//app $cnn->Execute($sql_homo);
                  if($homol2)
                  {
                    if(count($homol)>0)
                    {
                      $valor_homo=$homol2[0]['valor_serviciop'];
                    }
                  }

                  $id_llanogas_homo=$id_llanogas_homo.",'".$id_homo."')";
                  $valor_llanogas_homo=$valor_llanogas.",".$valor_homo.")";
                  $valor_sum_recaudo_llano_homo=$valor_llanogas+$valor_homo;

                  if($valor_sum_recaudo_llano_homo==$valorre)
                  {
                    $operador_sql="in(";
                  }
                  else
                  {
                    array_push($val, array("status" =>0,"msg"=>"El valor de recaudo Enviado No concuerda con el Valor Registrado"));
                    $this->log->logs("********************Termina Validar Transaccion ***********************" ,$val);
                    return new JsonResponse($val);
                    exit(0);
                  }
                }
              }
            }

            $sql=" SELECT factura ,v.id_deta_factura, valor_bruto as valor, v.pin, 'Pago Servicios Publicos' AS descripcion, v.estado, '0' AS status_nequi, login, v.id_deta_factura AS id_transac,'' AS status_code,
            CASE WHEN v.estado='0' THEN 'Confirmado' ELSE 'Con problemas'  END  AS status_msm,'' AS transactionid,cast(v.fecha_recaudo as date) AS dia,
            hora,'5' AS tipo_request,TO_CHAR(cast(v.fecha_recaudo AS date) :: DATE, 'dd TMMon, yyyy') AS fecha_grupo,v.fecha_recaudo AS fecha_sys,v.id_proveedor as tercero ,  r.dif_redondeo
            FROM vista_recaudos_sgc v  , llanogas_recaudado_ws r
            WHERE
            login='$nickname'
            AND v.estado IN ('0')
            and v.id_proveedor in ('8','128','246','199','129','172')
            and v.id_deta_factura $operador_sql $id_llanogas_homo
            and v.valor_bruto $operador_sql $valor_llanogas_homo
            and date(v.fecha_recaudo)='".$fecha_explo[0]."'
            and r.id_deta_factura =v.id_deta_factura
            order by v.fecha_recaudo desc ";

          }
          else // demas convenios
          {
            $complemento="";
            $tabla_recaudo="";

            if($tercero=='8')
            {
              $tabla_recaudo= ",emsa_recaudado r";
            }
            else if($tercero=='128')
            {
              $tabla_recaudo= ",acueducto_recaudado r";
            }
            else if($tercero=='246')
            {
              $tabla_recaudo= ",edesa_recaudado r";
            }


            if($tercero='246') //edesa
            {
              $segundos = strtotime($fecha_re_hh);
              $segundos=$segundos+300; // se agregan los 5 minutos
              $fecha_mas_minutos=date('Y-m-d H:i', $segundos);
              $complemento="and r.id_deta_factura=v.id_deta_factura and date(r.fecha_recaudo)>='$fecha_re_hh' and  date(r.fecha_recaudo)<='$fecha_mas_minutos'";
            }


            $sql="SELECT factura ,v.id_deta_factura, valor_bruto as valor, v.pin, 'Pago Servicios Publicos' AS descripcion, v.estado, '0' AS status_nequi, login, v.id_deta_factura AS id_transac,'' AS status_code,
            CASE WHEN v.estado='0' THEN 'Confirmado' ELSE 'Con problemas'  END  AS status_msm,'' AS transactionid,cast(v.fecha_recaudo as date) AS dia,
            hora,'5' AS tipo_request,TO_CHAR(cast(v.fecha_recaudo AS date) :: DATE, 'dd TMMon, yyyy') AS fecha_grupo,v.fecha_recaudo AS fecha_sys,v.id_proveedor as tercero , r.dif_redondeo
            FROM vista_recaudos_sgc v  $tabla_recaudo
            WHERE
            login='$nickname'
            AND v.estado IN ('0')
            and v.id_proveedor in ('8','128','246','199','129','172')
            and v.id_deta_factura=$facturaid
            and v.valor_bruto = $valorre
            and date(v.fecha_recaudo)='".$fecha_explo[0]."'
            and r.valor_redondeado=$valor_redondeado
            $complemento
            order by v.fecha_recaudo desc ";
          }

          $res=$this->cnn->query('0', $sql);
          $con=0;
          $cliente=null;
          $total_g=0;
          $valor_dif_redon=0;
          $id_regis=null;
          $name_ref="";

          if(count($res)>0)
          {
            foreach ($res as $key => $row)
            {
              $hora_recaudo=strtotime($row["hora"]);
              $hh_peticion_recaudo=strtotime($fecha_explo[1]);
              $hh_mas_cinco=$hh_peticion_recaudo+300;
              if($hora_recaudo>=$hh_peticion_recaudo && $hora_recaudo <=$hh_mas_cinco )
              {
                $con++;
                if($row["tercero"]=='129' || $row["tercero"]=='172' ) //lanogas
                {
                  if($row["tercero"]=='129')
                  {
                    $val_rec=$row['valor'];//$row[5];//valor recibo
                    $name_ref="Llanogas : ".$row['factura']."";
                    $id_reg_ll_bio="Llanogas : ".$row['id_deta_factura']."";//$row[0];
                    $pin=$row['pin'];//row[2];
                    $fechar=$row['dia']." ".$row["hora"] ;//$row[3];
                    $valor_dif_redon+=$row['dif_redondeo'];
                  }
                  else if($row["tercero"]=='172')
                  {
                    $val_rec=$row['valor'];//$row[5];//valor recibo
                    $name_ref="Bioagricola : ".$row['factura']."";
                    $id_reg_ll_bio="Bioagricola : ".$row['id_deta_factura']."";//$row[0];
                    $pin=$row['pin'];//row[2];
                    $fechar=$row['dia']." ".$row["hora"] ;//$row[3];
                    $valor_dif_redon+=$row['dif_redondeo'];
                  }
                    $id_regis.=$id_reg_ll_bio."\n";
                    $cliente.=$name_ref."\n";
                    $total_g+=(int)$val_rec;
                }
                else if($row["tercero"]=='8') //emsa
                {
                  $id_regis= $row['id_deta_factura'];//$r_p->fields[0];
                  $fechar=$row['dia']." ".$row["hora"] ;//$row[3];
                  $pin=$row['pin'];//$r_p->fields[2];
                  $cliente=$row['factura'];//$r_p->fields[4];
                   //$total=number_format($row['valor'],0,'.',',');
                   $total_g=$row['valor'];
                   $valor_dif_redon+=$row['dif_redondeo'];
                }
                else if($row["tercero"]=='128') //acueducto
                {
                  $id_regis= $row['id_deta_factura'];//$r_p->fields[0];
                  $fechar=$row['dia']." ".$row["hora"] ;//$row[3];
                  $pin=$row['pin'];//$r_p->fields[2];
                  $cliente=(int)$row['factura'];//$r_p->fields[4];
                   //$total=number_format($row['valor'],0,'.',',');
                   $total_g=$row['valor'];
                   $valor_dif_redon+=$row['dif_redondeo'];
                }
                else if($row["tercero"]=='246') // Edesa
                {
                  $id_regis= $row['id_deta_factura'];//$r_p->fields[0];
                  $fechar=$row['dia']." ".$row["hora"] ;//$row[3];
                  $pin=$row['pin'];//$r_p->fields[2];
                  $cliente=(int)$row['factura'];//$r_p->fields[4];
                  //$total=number_format($row['valor'],0,'.',',');
                  $total_g=$row['valor'];
                  $valor_dif_redon+=$row['dif_redondeo'];
                }
               /*else if($row["tercero"]=='199') //congente
                {
                  $id_regis= $row['id_deta_factura'];//$r_p->fields[0];
                  $fechar=$row['dia']." ".$row["hora"] ;//$row[3];
                  $pin=$row['pin'];//$r_p->fields[2];
                  $cliente=(int)$row['factura'];//$r_p->fields[4];
                  $total=number_format($row['valor'],0,'.',',');
                }*/
              }
              else
              {
                $con=0;
              }
            }

          }

          if($con>=1)
          {
            $valor_redon_valida=$total_g+$valor_dif_redon;
            if($valor_redon_valida==$valor_redondeado)
            {
              $this->log->logs('Valor redondeado Regis:'. $valor_dif_redon);
              $this->log->logs('total g:'. $total_g);
              $total=number_format($total_g,0,'.',',');
            }
            else
            {
              $this->log->logs('Valor redondeado Regis:'. $valor_dif_redon);
              $this->log->logs('total g:'. $total_g);
              array_push($val, array("status" =>0,"msg"=>"El valor de redondeo Enviado No concuerda con el Valor de Redondeo Registrado"));
              $this->log->logs("********************Termina Validar Transaccion ***********************" ,$val);
              return new JsonResponse($val);
              exit(0);
            }
            array_push($val, array("status" =>"1","id_pago"=>$id_regis,"fechar"=>$fechar,"pin"=>$pin,"cliente"=>$cliente,"total"=>$total));
          }
          else
          {
            array_push($val, array("status" =>0,"msg"=>"No se encontro registro de recaudo"));
          }

        }
        if($con=="6") //bemovil
        {
          $this->log->logs("********************Inicia Verificacion Transaccion Bemovil***********************");
          $dt = explode("|",$array);
          $this->log = new Log('app_sgc',$dt[0],$this->ruta);

          $datos=explode("|", $array);
          $nickname=$datos[0];//nickanme vendedora o usuario
          $id_ope=$datos[1];//siempre debe ser emsa
          $total=$datos[2];//total
          $telefo=$datos[3];//telefono o correo dependiendo del servicio
          $id_paq=$datos[4];
          $id_pro=$datos[5];
          $tipo_r=$datos[6];
          $tipou=$datos[7];
          $placa=$datos[8];//para runt y pines
          $imei=$datos[9];//imei o mac
          $fecha_r_hh=$datos[10];//fecha y hora del recaudo cuando se envio por primera vez desde el client que consume.


          if($nickname!="" && $id_pro!="" && $tipo_r!="")
          {
            $segundos = strtotime($fecha_r_hh);
            $segundos=$segundos+300; // se agregan los 5 minutos
            $fecha_mas_minutos=date('Y-m-d H:i', $segundos);

            if ($tipo_r=="0" || $tipo_r=="1") //Recargas y paquetes
            {
              $sql1="select r.id,r.fecha_sys from bemovil_recargas_recaudado r, (SELECT MAX(fecha_sys) from bemovil_recargas_recaudado
              where tel='$telefo' and valor='$total' and usuario='$nickname' and estado='0' and id_operador='$id_ope' and fecha_sys>='$fecha_r_hh' and fecha_sys<='$fecha_mas_minutos') f
              where f.max=r.fecha_sys and r.tel='$telefo' and r.valor='$total' and r.usuario='$nickname' and r.estado='0' and r.id_operador='$id_ope'  and fecha_sys>='$fecha_r_hh' and fecha_sys<='$fecha_mas_minutos'";
            }
            else if($tipo_r=="2")//runt
            {
              $sql1="select r.id,r.fecha_sys from bemovil_hvehicular_recaudado r, (SELECT MAX(fecha_sys) from bemovil_hvehicular_recaudado
              where dest_correo='$telefo' and valor='$total' and usuario='$nickname' and estado='0' and placa='$placa' and fecha_sys>='$fecha_r_hh' and fecha_sys<='$fecha_mas_minutos') f
              where f.max=r.fecha_sys and r.dest_correo='$telefo' and r.valor='$total' and r.usuario='$nickname' and r.estado='0' and r.placa='$placa' and fecha_sys>='$fecha_r_hh' and fecha_sys<='$fecha_mas_minutos'";
            }
            else if($tipo_r=="3")//Pines
            {
              $sql1="select r.id,r.fecha_sys from bemovil_pines_recaudado r, (SELECT MAX(fecha_sys) from bemovil_pines_recaudado
              where correo='$telefo' and valor='$total' and usuario='$nickname' and estado='0' and id_pin='$id_pro' and fecha_sys>='$fecha_r_hh' and fecha_sys<='$fecha_mas_minutos') f
              where f.max=r.fecha_sys and r.correo='$telefo' and r.valor='$total' and r.usuario='$nickname' and r.estado='0' and r.id_pin='$id_pro' and fecha_sys>='$fecha_r_hh' and fecha_sys<='$fecha_mas_minutos'";
            }

            $st=$this->cnn->query('0', $sql1);//sgc $cnn->Execute($sql1);
            if(count($st)>0)
            {
                if($tipo_r=="0" ) //recarga
                {
                  $id_r=$st[0]["id"];
                  $sql_impri="select  r.id,r.cant,r.fecha,r.hora,r.tel,r.valor,r.operador,r.usuario,r.pdv,t1.nombre,
                  o.descripcion as operador,p.descripcion as producto,t.descripcion as tipo_producto,r.pin
                  from bemovil_recargas_recaudado r,bemovil_tipo_paquete t,bemovil_paquetes p,bemovil_tipo_operador o ,territorios t1
                  where r.id='".$id_r."'
                  and r.id_operador=o.codigo
                  and p.tipo_paque=t.id
                  and p.id_paq=r.id_paq
                  and r.id_operador=p.id_ope
                  and t1.codigo=r.pdv
                  and ('".date("Y-m-d", strtotime("-1 day"))."'>=o.fecha_ini and
                  '".date("Y-m-d", strtotime("-1 day"))."'<=o.fecha_fin  or '".date("Y-m-d", strtotime("-1 day"))."'>=o.fecha_ini and o.fecha_fin is null)";

                  $sql1="select nro_resolucion, fecha_resolucion, num_ini, num_fin, prefijo
                  from bemovil_resolucion_dian
                  where fecha_ini<='".date('Y-m-d')."' and (fecha_fin>='".date('Y-m-d')."' or fecha_fin is null)  ";

                  $res1=$this->cnn->query('0', $sql1);//sgc $cnn->Execute($sql1);

                  if(count($res1)>0)//($res1->RecordCount()!=0)
                  {
                    $resu_dian=$res1[0]['nro_resolucion'];//$res1->fields[0];
                    $fecha_dian=$res1[0]['fecha_resolucion'];//$res1->fields[1];
                    $num_i=$res1[0]['num_ini'];//$res1->fields[2];
                    $num_f=$res1[0]['num_fin'];//$res1->fields[3];
                    $prefi=$res1[0]['prefijo'];//$res1->fields[4];
                  }

                  $result= $this->cnn->query('0', $sql_impri);//sgc $cnn->Execute($sql_impri);

                  if(count($result)>0)//($result->fields>0)
                  {
                    $nombre=$result[0]['operador'];//$result->fields[6];
                    $valor=$result[0]['valor'];//$result->fields[5];
                    $fecha_rel=$result[0]['fecha'];//$result->fields[2];
                    $fecha_r=$result[0]['fecha']." Hora :".$result[0]['hora'];//$result->fields[2]." Hora :".$result->fields[3];
                    $recaudador=$result[0]['usuario'];//$result->fields[7];
                    $pventa_u=$result[0]['nombre'];//$result->fields[9];
                    $pin=$result[0]['pin'];//$result->fields[13];
                    $celu=$result[0]['tel'];//$result->fields[4];
                    $name_plan=$result[0]['producto'];//$result->fields[11];
                    $cc=explode("CV", $recaudador);
                  }

                  if($result)
                  {
                    $this->log->logs("Va a devolver datos transaccion|tipo_r :0");
                    array_push($val, array("status" =>1,"id_pago"=>$id_r,"nombre"=>$nombre,"total" =>number_format($valor,0,'.',','),"fecha_rel" =>$fecha_rel,"fechar" =>$fecha_r,"pventa_u" =>$pventa_u,"pin" =>$pin,"recaudador" =>$cc[1],"celu"=>$celu,"name_plan"=>$name_plan,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi));
                  }
                  else
                  {
                    $valor="2|Error al generar consulta transaccion.";
                    array_push($val, array("status" =>0,"msm"=>$valor,"code"=>200));
                  }
                }
                elseif($tipo_r=="1") //paquetes
                {
                  $id_r=$st[0]["id"];
                  $sql_impri="select  r.id,r.cant,r.fecha,r.hora,r.tel,r.valor,r.operador,r.usuario,r.pdv,t1.nombre,
                  o.descripcion as operador,p.descripcion as producto,t.descripcion as tipo_producto,r.pin
                  from bemovil_recargas_recaudado r,bemovil_tipo_paquete t,bemovil_paquetes p,bemovil_tipo_operador o ,territorios t1
                  where r.id='".$id_r."'
                  and r.id_operador=o.codigo
                  and p.tipo_paque=t.id
                  and p.id_paq=r.id_paq
                  and r.id_operador=p.id_ope
                  and t1.codigo=r.pdv
                  and ('".date("Y-m-d", strtotime("-1 day"))."'>=o.fecha_ini and
                  '".date("Y-m-d", strtotime("-1 day"))."'<=o.fecha_fin  or '".date("Y-m-d", strtotime("-1 day"))."'>=o.fecha_ini and o.fecha_fin is null)";

                  $sql1="select nro_resolucion, fecha_resolucion, num_ini, num_fin, prefijo
                  from bemovil_resolucion_dian
                  where fecha_ini<='".date('Y-m-d')."' and (fecha_fin>='".date('Y-m-d')."' or fecha_fin is null)  ";

                  $res1=$this->cnn->query('0', $sql1);//sgc $cnn->Execute($sql1);
                  //echo $sql1;

                  if(count($res1)>0)//($res1->RecordCount()!=0)
                  {
                    $resu_dian=$res1[0]['nro_resolucion'];//$res1->fields[0];
                    $fecha_dian=$res1[0]['fecha_resolucion'];//$res1->fields[1];
                    $num_i=$res1[0]['num_ini'];//$res1->fields[2];
                    $num_f=$res1[0]['num_fin'];//$res1->fields[3];
                    $prefi=$res1[0]['prefijo'];//$res1->fields[4];
                  }

                  $result= $this->cnn->query('0', $sql_impri);//sgc $cnn->Execute($sql_impri);

                  if(count($result)>0)//($result->fields>0)
                  {
                    $nombre=$result[0]['operador'];//$result->fields[6];
                    $valor=$result[0]['valor'];//$result->fields[5];
                    $fecha_rel=$result[0]['fecha'];//$result->fields[2];
                    $fecha_r=$result[0]['fecha']." Hora :".$result[0]['hora'];//$result->fields[2]." Hora :".$result->fields[3];
                    $recaudador=$result[0]['usuario'];//$result->fields[7];
                    $pventa_u=$result[0]['nombre'];//$result->fields[9];
                    $pin=$result[0]['pin'];//$result->fields[13];
                    $celu=$result[0]['tel'];//$result->fields[4];
                    $name_plan=$result[0]['producto'];//$result->fields[11];
                    $cc=explode("CV", $recaudador);
                  }

                  if($result)
                  {
                    $this->log->logs("Va a devolver datos transaccion| tipo_r: 1 ");
                    array_push($val, array("status" =>1,"id_pago"=>$id_r,"nombre"=>$nombre,"total" =>number_format($valor,0,'.',','),"fecha_rel" =>$fecha_rel,"fechar" =>$fecha_r,"pventa_u" =>$pventa_u,"pin" =>$pin,"recaudador" =>$cc,"celu"=>$celu,"name_plan"=>$name_plan,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi));
                  }
                  else
                  {
                    $valor="2|Error al generar consulta transaccion.";
                    array_push($val, array("status" =>0,"msm"=>$valor,"code"=>200));
                  }

                }
                elseif($tipo_r=="2") // runt
                {
                  $id_r=$st[0]["id"];
                  $sql_id="select r.id,CAST(r.fecha_r as date) as fecha_r,r.valor,r.usuario,r.pdv,t.nombre,
                  h.descripcion as operador,r.pin,cast(r.fecha_r as time)  as hora,r.placa,r.dest_correo
                  from bemovil_hvehicular_recaudado r,territorios t,bemovil_hvehicular h
                  where r.id=".$id_r."
                  and t.codigo=r.pdv
                  and r.id_pro=h.id";
                  $res1=$this->cnn->query('0', $sql_id);//sgc $cnn->Execute($sql_id);
                  $valor="0";
                  if($res1)
                  {
                    $fecha_r=$res1[0]['fecha_r'];//   $res1->fields[1];
                    //$valor= number_format($res1->fields[2],0,'.',',');
                    $valor= number_format($res1[0]['valor'],0,'.',',');
                    $usuario=$res1[0]['usuario'];//   $res1->fields[3];
                    $operador=$res1[0]['operador'];//  $res1->fields[6];
                    $pin=$res1[0]['pin'];//   $res1->fields[7];
                    $hora=$res1[0]['hora'];//   $res1->fields[8];
                    $placa=$res1[0]['placa'];//   $res1->fields[9];
                    $mail=$res1[0]['correo'];//$res1->fields[10];//
                    $fechar=$fecha_r." Hora ".$hora;
                  }

                  if($res1)
                  {
                    $this->log->logs("Va a devolver datos transaccion| tipo_r: 2 ");
                    array_push($val, array("status" =>1,"id_pago"=>$id_r,"fechar"=>$fechar,"fecha_rel"=>$fecha_r,"total"=>$valor,"recaudador"=>$usuario,"name_plan"=>$operador,"nombre"=>"Bemovil Runt","pin"=>$pin,"placa"=>$placa,"mail"=>$mail));
                  }
                  else
                  {
                    $valor="2|Error al generar consulta transaccion.";
                    array_push($val, array("status" =>0,"msm"=>$valor,"code"=>200));
                  }
                }
                elseif($tipo_r=="3") //pines
                {
                  $id_r=$st[0]["id"];
                  $sql1="select nro_resolucion, fecha_resolucion, num_ini, num_fin, prefijo
                  from bemovil_resolucion_dian
                  where fecha_ini<='".date('Y-m-d')."' and (fecha_fin>='".date('Y-m-d')."' or fecha_fin is null)  ";

                  $res1=$this->cnn->query('0', $sql1);//sgc $cnn->Execute($sql1);
                  //echo $sql1;

                  if(count($res1)>0)//($res1->RecordCount()!=0)
                  {
                    $resu_dian=$res1[0]['nro_resolucion'];//$res1->fields[0];
                    $fecha_dian=$res1[0]['fecha_resolucion'];//$res1->fields[1];
                    $num_i=$res1[0]['num_ini'];//$res1->fields[2];
                    $num_f=$res1[0]['num_fin'];//$res1->fields[3];
                    $prefi=$res1[0]['prefijo'];//$res1->fields[4];
                  }

                  $sql_id="select  r.id,CAST(r.fecha_r as date) as fecha_r,r.valor,r.usuario,r.pdv,t.nombre,h.concepto,r.pin,
                  cast(r.fecha_r as time)  as hora,r.correo,r.tele,r.nro_fact
                  from bemovil_pines_recaudado r,territorios t,bemovil_pines h
                  where r.id='".$id_r."'
                  and t.codigo=r.pdv
                  and r.id_pin=h.id";
                  $res1=$this->cnn->query('0', $sql_id);//sgc $cnn->Execute($sql_id);

                  $valor="0";
                  if($res1)
                  {
                    $fecha_r=$res1[0]['fecha_r'];//   $res1->fields[1];
                    //$valor= number_format($res1->fields[2],0,'.',',');
                    $valor= number_format($res1[0]['valor'],0,'.',',');
                    $usuario=$res1[0]['usuario'];//   $res1->fields[3];
                    $operador=$res1[0]['operador'];//  $res1->fields[6];
                    $pin=$res1[0]['pin'];//   $res1->fields[7];
                    $hora=$res1[0]['hora'];//   $res1->fields[8];
                    $placa=$res1[0]['placa'];//   $res1->fields[9];
                    $mail=$res1[0]['correo'];//$res1->fields[10];//
                    $fechar=$fecha_r." Hora ".$hora;
                    $nro_fact=$res1[0]['nro_fact'];//$res1->fields[11];
                  }

                  if($res1)
                  {
                    $this->log->logs("Va a devolver datos transaccion| tipo_r: 3 ");
                    array_push($val, array("status" =>1,"id_pago"=>$id_r,"fechar"=>$fechar,"fecha_rel"=>$fecha_r,"total"=>$valor,"recaudador"=>$usuario,"name_plan"=>$operador,"nombre"=>"Bemovil Pines","pin"=>$pin,"placa"=>$placa,"mail"=>$mail,"nro_fact"=>$nro_fact,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi));
                  }
                  else
                  {
                    $valor="2|Error al generar consulta transaccion.";
                    array_push($val, array("status" =>0,"msm"=>$valor,"code"=>200));
                  }
                }
            }
            else
            {
              $valor="2|No registro Ninguna transaccion dentro de los rangos de fecha y hora del recaudo. Si desea consulte Nuevamente";
              $this->log->logs($valor);
              array_push($val, array("status" =>0, "codigo_error"=>2, "msm"=>$valor,"code"=>200));
            }
          }
          else
          {
            array_push($val, array("status" =>0,"msm"=>"Verifique que envio valores para la consulta.","code"=>200));
          }
          $this->log->logs("********************Termina Validar Transaccion bemovil ***********************" ,$val);
        }
        if($con=="24") //betplay recargas-retiros
        {
          $this->log->logs("********************Inicia Verificacion Transaccion Recaudos Betplay***********************");
          $dt = explode("|",$array);
          $this->log = new Log('app_sgc',$dt[0],$this->ruta);

          $datos=explode("|", $array);
          $nickname=$datos[0]; //vendedor
          $tipo=$datos[1]; //tipo -recarga -retiro
          $valor=$datos[2]; // tercero
          $id_apost=$datos[3]; // id apostador
          $fecha_re_hh=$datos[4]; // fecha y hora de peticion al momento de enviar primera solicitud de recaudo
          $fecha_explo=(explode(" ",$fecha_re_hh)); // [0] =fecha  [1]=hora
          $mac=$datos[5];
          $pin_retiro=$datos[6];

          $segundos = strtotime($fecha_re_hh);
          $segundos=$segundos+300; // se agregan los 5 minutos
          $fecha_mas_minutos=date('Y-m-d H:i', $segundos);

          if($tipo=="0") //recarga betplay
          {
            $sql="select max(c.fecha_recaudado), c.id,c.usuario,
            (select nombres||' '||apellido1 from personas where documento::text=substring(c.usuario,3)) as nm_vendedor,
            c.pdv,t.nombre,c.id_apostador,c.valor,cast(c.fecha_recaudado as date) as fecha_r,cast(c.fecha_recaudado as time) as hora,
            c.pin,c.pin_betplay ,
            CASE WHEN r.tipo =0 THEN 'Recargas Betplay' WHEN r.tipo =1 THEN 'Retiros Betplay' WHEN r.tipo =2
            THEN 'Solicitud Retiro Pin' WHEN r.tipo =3 THEN 'Apuestas Rapidas' ELSE 'Producto desconocido' END as tipo_pro,
            r.tipo,c.nro_factura
            from cem_recaudado c , cem_regis_detalle r, territorios  t
            where
            c.estado='0'
            and t.codigo=c.pdv::integer
            and r.tipo='$tipo'
            and c.usuario ='$nickname'
            and c.valor='$valor'
            and c.id_apostador ='$id_apost'
            and r.mac='$mac'
            and c.fecha_recaudado>='$fecha_re_hh' and c.fecha_recaudado<='$fecha_mas_minutos'
            group by c.id,t.nombre,r.tipo";

            $res=$this->cnn->query('0', $sql);

            if(count($res)>0)
            {
              $nombre=$res[0]['nm_vendedor'];//$result->fields[1];
              $valor=$res[0]['valor'];//$result->fields[5];
              $fecha_rel=$res[0]['fecha_r'];//$result->fields[6];
              $fecha_r=$res[0]['fecha_r']." Hora :".$res[0]['hora'];//$result->fields[6]." Hora :".$result->fields[7];
              $recaudador=$res[0]['usuario'];//$result->fields[0];
              $pventa_u=$res[0]['nombre'];//$result->fields[3];
              $pin=$res[0]['pin'];//$result->fields[8];
              $celu=$res[0]['id_apostador'];//$result->fields[4];
              $name_plan=$res[0]['tipo_pro'];//$result->fields[10];
              $cc=explode("CV", $recaudador);
              $nro_fact=$res[0]['nro_factura'];//$result->fields[12];

              $sql1="select nro_resolucion, fecha_resolucion, num_ini, num_fin, prefijo
              from bemovil_resolucion_dian
              where fecha_ini<='".date('Y-m-d')."' and (fecha_fin>='".date('Y-m-d')."' or fecha_fin is null)  ";

              $res1=$this->cnn->query('0', $sql1);//sgc $cnn->Execute($sql1);
              //echo $sql1;
              if(count($res1)>0)//($res1->RecordCount()!=0)
              {
                $resu_dian=$res1[0]['nro_resolucion'];//$res1->fields[0];
                $fecha_dian=$res1[0]['fecha_resolucion'];//$res1->fields[1];
                $num_i=$res1[0]['num_ini'];//$res1->fields[2];
                $num_f=$res1[0]['num_fin'];//$res1->fields[3];
                $prefi=$res1[0]['prefijo'];//$res1->fields[4];
              }

              array_push($val, array("status" =>1,"id_pago"=>$id_r,"nombre"=>$nombre,"total" =>number_format($valor,0,'.',','),"fecha_rel" =>$fecha_rel,"fechar" =>$fecha_r,"pventa_u" =>$pventa_u,"pin" =>$pin,"recaudador" =>$cc[1],"celu"=>$celu,"name_plan"=>$name_plan,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi,"nro_fact"=>$nro_fact));
            }
            else
            {
              $valor="1|No registro Ninguna transaccion dentro de los rangos de fecha y hora del recaudo. Si desea consulte Nuevamente";
              $this->log->logs($valor);
              array_push($val, array("status" =>0, "codigo_error"=>2, "msm"=>$valor,"code"=>200));
            }
          }
          else if($tipo=="1") //retiro betplay
          {
            $sql="select max(c.fecha_recaudado), c.id,c.usuario,
            (select nombres||' '||apellido1 from personas where documento::text=substring(c.usuario,3)) as nm_vendedor,
            c.pdv,t.nombre,c.id_apostador,c.valor,cast(c.fecha_recaudado as date) as fecha_r,cast(c.fecha_recaudado as time) as hora,
            c.pin,c.pin_betplay ,
            CASE WHEN r.tipo =0 THEN 'Recargas Betplay' WHEN r.tipo =1 THEN 'Retiros Betplay' WHEN r.tipo =2
            THEN 'Solicitud Retiro Pin' WHEN r.tipo =3 THEN 'Apuestas Rapidas' ELSE 'Producto desconocido' END as tipo_pro,
            r.tipo,c.nro_factura
            from cem_recaudado c , cem_regis_detalle r, territorios  t
            where
            c.estado='0'
            and t.codigo=c.pdv::integer
            and r.tipo='$tipo'
            and c.usuario ='$nickname'
            and c.valor='$valor'
            and c.id_apostador ='$id_apost'
            and r.pin_retiro ='$pin_retiro'
            and r.mac='$mac'
            and c.fecha_recaudado>='$fecha_re_hh' and c.fecha_recaudado<='$fecha_mas_minutos'
            group by c.id,t.nombre,r.tipo";

            $res=$this->cnn->query('0', $sql);

            if(count($res)>0)//($result->fields>0)
            {
              $id_r=$res[0]['id'];//$result->fields[1];
              $nombre=$res[0]['nm_vendedor'];//$result->fields[1];
              $valor=$res[0]['valor'];//$result->fields[5];
              $fecha_rel=$res[0]['fecha_r'];//$result->fields[6];
              $fecha_r=$res[0]['fecha_r']." Hora :".$res[0]['hora'];//$result->fields[6]." Hora :".$result->fields[7];
              $recaudador=$res[0]['usuario'];//$result->fields[0];
              $pventa_u=$res[0]['nombre'];//$result->fields[3];
              $pin=$res[0]['pin'];//$result->fields[8];
              $celu=$res[0]['id_apostador'];//$result->fields[4];
              $name_plan=$res[0]['tipo_pro'];//$result->fields[10];
              $cc=explode("CV", $recaudador);
              $nro_fact=$res[0]['nro_factura'];//$result->fields[12];

              $sql1="select nro_resolucion, fecha_resolucion, num_ini, num_fin, prefijo
              from bemovil_resolucion_dian
              where fecha_ini<='".date('Y-m-d')."' and (fecha_fin>='".date('Y-m-d')."' or fecha_fin is null)  ";

              $res1=$this->cnn->query('0', $sql1);//sgc $cnn->Execute($sql1);
              //echo $sql1;
              if(count($res1)>0)//($res1->RecordCount()!=0)
              {
                $resu_dian=$res1[0]['nro_resolucion'];//$res1->fields[0];
                $fecha_dian=$res1[0]['fecha_resolucion'];//$res1->fields[1];
                $num_i=$res1[0]['num_ini'];//$res1->fields[2];
                $num_f=$res1[0]['num_fin'];//$res1->fields[3];
                $prefi=$res1[0]['prefijo'];//$res1->fields[4];
              }

              array_push($val, array("status" =>1,"id_pago"=>$id_r,"nombre"=>$nombre,"total" =>number_format($valor,0,'.',','),"fecha_rel" =>$fecha_rel,"fechar" =>$fecha_r,"pventa_u" =>$pventa_u,"pin" =>$pin,"recaudador" =>$cc[1],"celu"=>$celu,"name_plan"=>$name_plan,"resol"=>$resu_dian,"fecha_resol"=>$fecha_dian,"resul_num_i"=>$num_i,"resul_num_f"=>$num_f,"prefi"=>$prefi,"nro_fact"=>$nro_fact));
            }
            else
            {
              $valor="2|No registro Ningun Retiro dentro de los rangos de fecha y hora del recaudo. Si desea consulte Nuevamente";
              $this->log->logs($valor);
              array_push($val, array("status" =>0, "codigo_error"=>2, "msm"=>$valor,"code"=>200));
            }
          }
        }
      }
      else
      {
        array_push($val, array("status" =>0,"msm"=>"Array sin valores.","code"=>200));
      }

      $this->log->logs("********************Termina Validar Transaccion ***********************" ,$val);
      return new JsonResponse($val);
    }
  }
?>