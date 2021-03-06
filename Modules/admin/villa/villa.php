<?php
/**
 * Created by PhpStorm.
 * User: musaatalay
 * Date: 25.10.2014
 * Time: 20:41
 */

namespace System\Modules;

use System\Libraries\Request;
use System\Engines;
use System\Libraries;

Request::load("Libraries/Module.php");

class villa extends Module {

    private $MySQL;

    public function __construct()
    {

        $this->MySQL = (new Engines\MySQL())->connect();

        $this->MySQL->character("utf8");

        if (!$this->MySQL->Status()) {

            exit($MySQL->ErrorHandler()->ErrorMessage());

        }

        $Config = $this->getSystemConfig("/config");

        $this->data["config"]["title"] = $Config["project_name"];
        $this->data["config"] = array_merge($this->data["config"], $Config["administrator"]);

    }

    public function index(){

        $this->data["Villalar"] = $this->MySQL->select("/villa")->asc("/id")->execute(["fetch" => "all"], true);

        return $this->render();

    }

    public function form(){

        $Villa = $this->MySQL->select("/villa:*")->where("/id:=:".Request::get("id"))->asc("id")->execute(["fetch" => "first"], true);

        $VillaGallery = $this->MySQL->select("/resimler:*")->where("/ref_id:=:".Request::get("id").";&&;type:=:villa")->execute(["fetch" => "all"], true);

        $GalleryElements = null;

        if(count($VillaGallery)>=1){

            $GalleryElements .= '<style type="text/css">.dz-default.dz-message{z-index: -1;}</style>';

        }

        foreach ($VillaGallery as $Gallery) {

            $GParse = explode("/", $Gallery->src);

            $GalleryElements .= '<div class="dz-preview dz-processing dz-image-preview dz-success">  <div class="dz-details">    <div class="dz-filename"><span data-dz-name="">'.$GParse[count($GParse)-1].'</span></div>    <div style="display: none;" class="dz-size" data-dz-size=""><strong>11</strong> KiB</div>    <img data-dz-thumbnail="" alt="'.$GParse[count($GParse)-1].'" title="'.$GParse[count($GParse)-1].'" src="../../villa/index/'.$Gallery->src.'">  </div>  <div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress="" style="width: 100%;"></span></div>  <div class="dz-success-mark"><span>✔</span></div>  <div class="dz-error-mark"><span>✘</span></div>  <div class="dz-error-message"><span data-dz-errormessage=""></span></div><button class="red resim-sil" data-content="{\'villa_id\':\''.Request::get("id").'\',\'id\':\''.$Gallery->id.'\'}"><span class="icon icon-s icon-trash"></span> Resmi Sil</button></div>';

        }

        $this->data = array(
            "VillaID" => Request::get("id"),
            "Villa" => $Villa,
            "GalleryElements" => $GalleryElements
        );

        return $this->render();

    }

    public function set(){

       if(Request::isPost("id")){

           return $this->update();

       }

       return $this->insert();

    }

    public function insert(){

        $Res = array("response" => false, "insert_id" => false);

        $Values = "/";

        foreach (Request::post() as $col => $val) {

            if(empty($val)||$val==null||strlen($val)<=0){

                continue;

            }

            $Values .= "{$col}::{$val};;";

        }

        $Values = rtrim($Values, ";;");

        $Insert = $this->MySQL->insert("/villa")->data($Values);

        $Res["response"] = $Insert->execute();

        $Res["insert_id"] = ($Res["response"]) ? $Insert->insertId() : false;

        Libraries\Response::header("json");

        Libraries\Response::json($Res);


    }

    public function update(){

        $Cols = array(
            "wifi" => 0,
            "gecelik_fiyat" => "0.00",
            "havuz" => 0,
            "uydu" => 0,
            "sicak_su" => 0,
            "lcd_tv" => 0,
            "jakuzi" => 0,
            "durumu" => 0,
            "active" => 0
        );

        $ThumbnailDir = "_assets/images/rooms/";

        $Res = array("response" => false, "insert_id" => Request::post("id"));

        if(count(Request::post())<=1){

            Libraries\Response::header("json");

            Libraries\Response::json($Res);

        }

        $_DATAS = array_merge($Cols, Request::post());

        $QueryString = "/villa/";

        foreach ($_DATAS as $col => $val) {

            $QueryString .= "{$col}::{$val};;";

        }

        $QueryString = rtrim($QueryString, ";;");

        $Update = $this->MySQL->update($QueryString)->where("/id:=:".Request::post("id"));

        $Res["response"] = $Update->execute();

        Libraries\Response::header("json");

        Libraries\Response::json($Res);

    }

    public function uploadGallery(){

        $StoreFolder = '../../villa/index/_assets/images/gallery/';

        $ParseName = explode(".", Request::file("file")['name']);

        $Ext = $ParseName[count($ParseName)-1];

        array_splice($ParseName, (count($ParseName)-1), 1);

        $FileName = $ParseName[0];

        if(count($ParseName)>=1){

            $FileName = implode(".",$ParseName);

        }

        $FileName = md5($FileName.rand(9, 99999).microtime()).".".$Ext;

        $tempFile = Request::file("file")['tmp_name'];

        $targetPath = dirname( __FILE__ ) ."/". $StoreFolder;

        $targetFile =  $targetPath.$FileName;

        $Upload = move_uploaded_file($tempFile,$targetFile);

        Libraries\Response::header("json");

        Libraries\Response::json(array("response" => true, "fileName" => $FileName));

    }

    public function addThumbnail(){

        //$Update = $this->MySQL->update("/villa")->data("/thumbnail::".Request::post("/thumbnail"))->where("/id:=:".Request::post('/villa_id'));

        $Switch = $this->MySQL->switch("/resimler")->poles("0/1");  #->where("/ref_id:=:".Request::post("/villa_id"))

    }

    public function addGallery(){

        $StorePath = '_assets/images/gallery/';

        $Res = array("response" => false, "insert_id" => false);

        $Villa = $this->MySQL->select("/villa:id, name, thumbnail/id:=:".Request::post("villa_id"))->asc("/id")->execute(["fetch" => "first"], true);

        $InsertQuery = "/ref_id::".Request::post("villa_id").";;src::".$StorePath.Request::post("file_name");
        $InsertQuery .= ";;alt::".$Villa->name.";;title::".$Villa->name.";;type::villa";

        if($Villa->thumbnail==""||$Villa->thumbnail==null||empty($Villa->thumbnail)||strlen($Villa->thumbnail)<=0){

            $this->MySQL->update("/villa/thumbnail::".$StorePath.Request::post("file_name"))->where("/id:=:".Request::post("villa_id"))->execute();

        }

        $Insert = $this->MySQL->insert("/resimler")->data($InsertQuery);

        $Execute = $Insert->execute();

        $Res["response"] = $Execute;

        $Res["insert_id"] = ($Res["response"]) ? $Insert->insertId() : false;

        Libraries\Response::header("json");

        Libraries\Response::json($Res);

    }

    public function removeGallery(){

        $StoreFolder = 'Modules/villa/index/_assets/images/gallery/';

        $Delete = $this->MySQL->delete("/resimler")->where("/id:=:".Request::post("gallery_id").";&&;ref_id:=:".Request::post("villa_id").";&&;type:=:villa");

        $RemoveStored = unlink($StoreFolder.Request::post("file_name"));

        Libraries\Response::header("json");

        Libraries\Response::json(array("response" => $Delete->execute(), "remove_stored" => $RemoveStored));

    }

    public function sil(){

        $Update = $this->MySQL->query("DELETE FROM villa WHERE id = ".Request::post("id"));

        $Res = array("response" => false);

        if($Update){

            $Res = array("response" => true);

        }

        Libraries\Response::header("json");

        Libraries\Response::json($Res);

    }

    public function active(){

        $Update = $this->MySQL->query("UPDATE villa SET active = 1 WHERE id = ".Request::post("id"));

        $Res = array("response" => false);

        if($Update){

            $Res = array("response" => true);

        }

        Libraries\Response::header("json");

        Libraries\Response::json($Res);

    }

    public function deactive(){

        $Update = $this->MySQL->query("UPDATE villa SET active = 0 WHERE id = ".Request::post("id"));

        $Res = array("response" => false);

        if($Update){

            $Res = array("response" => true);

        }

        Libraries\Response::header("json");

        Libraries\Response::json($Res);

    }

    public function VillaGallery(){

        $Gallery = $this->MySQL->select("/resimler:src,CASE rel WHEN 1 THEN 'true' WHEN 0 THEN 'false' END AS selected")->where("ref_id:=:".Request::post("villa_id")."&&type:=:villa")->execute(["fetch"=>"all"], true);

        Libraries\Response::header("javascript");

        Libraries\Response::json($Gallery);

    }

    public function test(){

        $Insert = $this->MySQL->insert("/villa")->data("/name::Deneme Adı;;description::Deneme Açıklama")->execute();

        var_dump($Insert);

    }

} 