<?php

defined('EXECG__') or die('<h1>404 - <strong>Not Found</strong></h1>');

class WareHouseModel extends ModelBase {

    public function getWareHouse(){  
        $query = "SELECT * from bodegas order by nombrebodega asc";
        $consulta = $this->db->executeQue($query);
        $bodegas;
        while ($row = $this->db->arrayResult($consulta)) {
            $otracon = "select * from movimientos where idbodega=" . $row['bodegaid'];
            $movs = $this->db->executeQue($otracon);
            $nummovs = $this->db->numRows($movs);
            $bodegas[] = array('id' => $row['bodegaid'],
                'nombre' => $row['nombrebodega'],
                'direccion' => $row['direccionbodega'],
                'movimientos'=> $nummovs);
        }
        return $bodegas;
    }

    public function getWareHousebyId() {
        $idbodega = $_GET['idware'];
        $query = "SELECT * from bodegas where bodegaid=$idbodega";
        $consulta = $this->db->executeQue($query);
        $bodega;
        while ($row = $this->db->arrayResult($consulta)) {
            ///traer la localidad para seleccionarla y buscar los barrios
            $idbarrio = $row['barrio'];
            $query2 = "SELECT * from barrios where idbarrio=$idbarrio";
            $consulta2 = $this->db->executeQue($query2);
            $localidad = null;
            while ($row2 = $this->db->arrayResult($consulta2)) {
                $localidad = $row2['idlocalidad'];
            }
            ///traer el departamento para seleccionarls y buscar las ciudades
            $idciudad = $row['ciudad'];
            $query3 = "SELECT * from ciudades where idciudad=$idciudad";
            $consulta3 = $this->db->executeQue($query3);
            $departamento = null;
            while ($row3 = $this->db->arrayResult($consulta3)) {
                $departamento = $row3['iddepartamento'];
            }
            $bodega = array('id' => $row['bodegaid'],
                'nombre' => $row['nombrebodega'],
                'direccion' => $row['direccionbodega'],
                'barrio' => $row['barrio'],
                'ciudad' => $row['ciudad'],
                'localidad' => $localidad,
                'departamento' => $departamento);
        }
        return $bodega;
    }

    public function getPerfiles() {
        $query = "SELECT * from perfiles order by nombreperfil asc";
        $consulta = $this->db->executeQue($query);
        $perfiles= array();
        while ($row = $this->db->arrayResult($consulta)) {
             if ($row['grupo'] != 'Estudiante' && $row['grupo'] != 'No usuario' && $row['grupo'] != 'Superadministrador') {
                $perfiles[] = array('id' => $row['idperfil'],
                    'nombre' => $row['nombreperfil'],
                    'grupo' => $row['grupo']);
            }
        }
        return $perfiles;
    }

    public function getUsuarios($idperfil) {
        $query = "SELECT * from usuarios where perfil=$idperfil and idbodega is NULL";
        $consulta = $this->db->executeQue($query);
        $usuarios= array();
        while ($row = $this->db->arrayResult($consulta)) {
            $usuarios[] = array('id' => $row['idusuario'],
                'nombre' => $row['nombreusuario']);
        }
        return $usuarios;
    }

    public function getUserWareHouse() {
        $idbodega = $_GET['idware'];
        $query = "SELECT u.idusuario, u.nombreusuario, p.nombreperfil, p.grupo
                    from usuarios u, perfiles p 
                    where u.idbodega=$idbodega and p.idperfil=u.perfil and p.grupo not in ('No usuario', 'Estudiante')";
        $consulta = $this->db->executeQue($query);
        $bodegas;
        while ($row = $this->db->arrayResult($consulta)) {
            $bodegas[] = array('id' => $row['idusuario'],
                'nombre' => $row['nombreusuario'],
                'perfil' => $row['nombreperfil']);
        }
        return $bodegas;
    }

    public function editWareHouse() {
        $query = "SELECT * from bodegas order by nombrebodega asc";
        $consulta = $this->db->executeQue($query);        
        while ($row = $this->db->arrayResult($consulta)) {
            $bodegas[] = array('id' => $row['bodegaid'],
                'nombre' => $row['nombrebodega'],
                'direccion' => $row['direccionbodega']);
        }
        return $bodegas;
    }

    public function createWareHouse() {
        $nombre = strtoupper($_POST["nombrebodega"]);
        $ciudad = $_POST["ciudades"];
        $barrio = $_POST["barrvin"];
        $direccion = $_POST["direccion"];
        $query = "select nextval('bodegas_idbodega_seq'::regclass) limit 1";
        $consult = $this->db->executeQue($query);
        $row = $this->db->arrayResult($consult);
        $idbodega = $row['nextval'];        
        $idverify = strrev(urlencode(base64_encode($idbodega)));
        $idid = sha1($idbodega);
        $query = "insert into bodegas values ($idbodega,$ciudad,'$nombre','$direccion',$barrio);";
        $query.= "insert into bodegasproductos(idbodega,idproducto,stock,stockmaximo,stockminimo,costo,preciobase) 
        SELECT $idbodega,idproducto,0, NULL,NULL,0,precio FROM productos";
        if ($this->db->executeQue($query)){             
            echo json_encode(array("respuesta"=>"si",
                "id"=>$idbodega,
                "nombre"=>$nombre,
                "direccion"=>$direccion,
                "idid"=>$idid,
                "verify"=>$idverify));
        } else {
            echo json_encode(array("respuesta"=>"no"));
        }
    } 

    public function createPermissionWareHouse() {     
        $idusuario = $_POST["usersbyperfil"]; 
        $idwarehouse = $_POST["idware"];
        $query = "update usuarios set idbodega=$idwarehouse where idusuario=$idusuario"; 
        if ($this->db->executeQue($query)) { 
            $consulta = $this->db->executeQue("SELECT * from usuarios u, perfiles p
                    where u.idusuario=$idusuario and u.perfil=p.idperfil");
            $usuario = null; 
            while ($row = $this->db->arrayResult($consulta)) { 
                $usuario = array('id' => $row['idusuario'],
                    'nombre' => $row['nombreusuario'],
                    'perfil' => $row['nombreperfil'],
                    'idverify' => strrev(urlencode(base64_encode($row['idusuario']))),
                    'idid' => sha1(time().$row['idusuario'].time()));
            }
            $respuesta['respuesta'] = 'si';
            $respuesta['newuser'] = $usuario;
            $respuesta['idbodega'] = $idwarehouse;
            echo json_encode($respuesta);
        } else {
            $respuesta['respuesta'] = 'no';
            echo json_encode($respuesta);
        }
    }

    public function deletePermissionWareHouse() {
        if (isset($_POST["verify"])) {
            $userid = base64_decode(urldecode(strrev(trim($_POST["verify"]))));
            $idbodega = trim($_GET['idware']);            
            $consulta = $this->db->executeQue("SELECT * from usuarios where idbodega=$idbodega and idusuario=$userid");            
            $total = $this->db->numRows($consulta);
            if ($total != 0) {
                $query2 = "update usuarios set idbodega=NULL where idusuario=$userid and idbodega=$idbodega";
                $this->db->executeQue($query2);
                $respuesta['res'] = 'si';
                $respuesta['idrow'] = $userid;
                echo json_encode($respuesta);
            } else {
                $respuesta['res'] = 'no';
                echo json_encode($respuesta);
            }
        }
    }

    public function updateWareHouse() {
        if (isset($_POST["verification"]) && isset($_POST["formid"])) {
            if ($_POST["formid"] == sha1(2989140)) {
                $bodegaid = base64_decode(urldecode(strrev($_POST["verification"])));
                $nombre = strtoupper($_POST["nombrebodega"]);
                $ciudad = $_POST["ciudades"];
                $barrio = $_POST["barrvin"];
                $direccion = $_POST["direccion"];
                $query = "update bodegas set nombrebodega='$nombre', direccionbodega='$direccion', ciudad=$ciudad," .
                        " barrio=$barrio where bodegaid=$bodegaid";
                if ($this->db->executeQue($query)) {
                    echo json_encode(array("respuesta"=>"si","id"=>$bodegaid,"nombre"=>$nombre,"direccion"=>$direccion));
                } else {
                    echo json_encode(array("respuesta"=>"no"));
                }
            }
        }
    }

    public function deleteWareHouse() {
        if (isset($_POST["verify"])) {
            $bodegaid = base64_decode(urldecode(strrev($_POST["verify"])));
            if ($this->verificarMovimientos($bodegaid)) {
                $query = "SELECT * from usuarios where idbodega=$bodegaid";
                $consulta = $this->db->executeQue($query);
                while ($row = $this->db->arrayResult($consulta)) {
                    $userid = $row['idusuario'];
                    $query2 = "update usuarios set idbodega=NULL where idusuario=$userid and idbodega=$bodegaid";
                    $this->db->executeQue($query2);
                }
                $query3 = "delete from bodegasproductos where idbodega=$bodegaid";
                if ($this->db->executeQue($query3)) {
                    $query4 = "delete from bodegas where bodegaid=$bodegaid";
                    if ($this->db->executeQue($query4)) {
                        $respuesta['res'] = 'si';
                        $respuesta['idrow'] = $bodegaid;
                        echo json_encode($respuesta);
                    } else {
                        $respuesta['res'] = 'no';
                        echo json_encode($respuesta);
                    }
                } else {
                    $respuesta['res'] = 'no';
                    echo json_encode($respuesta);
                }
            } else {
                $respuesta['res'] = 'no';
                echo json_encode($respuesta);
            }
        }
    }

    public function verificarMovimientos($idbodega) {
        $query = "SELECT * from movimientos where idbodega=$idbodega";
        $consulta = $this->db->executeQue($query);
        $total = $this->db->numRows($consulta);
        if ($total == 0) {
            return true;
        } else {
            return false;
        }
    }

}

?>