<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020.03.27.02

class PhpLivePerms{
  private $PhpLivePdo = null;

  public function __construct(&$PhpLivePdo = null){
    $this->PhpLivePdo = $PhpLivePdo;
  }

  public function Access($Options){
    if($this->PhpLivePdo === null){
      if(isset($Options["PhpLivePdo"]) == false){
        return false;
      }else{
        $PhpLivePdo = &$Options["PhpLivePdo"];
      }
    }else{
      $PhpLivePdo = $this->PhpLivePdo;
    }

    $return = ["r" => null, "w" => null, "o" => null];
    //Get resource id by name
    if(is_numeric($Options["Resource"]) == false){
      if(session_name() == "PHPSESSID"){
        $result = $PhpLivePdo->SQL("select resource_id
          from sys_resources
          where resource=?
            and site is null", [
          [1, $Options["Resource"], PdoStr]
        ]);
      }else{
        $result = $PhpLivePdo->SQL("select resource_id
          from sys_resources
          where resource=?
            and site=?", [
          [1, $Options["Resource"], PdoStr],
          [2, session_name(), PdoStr]
        ]);
      }
      $Options["Resource"] = $result[0][0];
    }
    // Permissions for everyone
    $result = $PhpLivePdo->SQL("select r,w,o
      from sys_perms
      where resource_id=?
        and group_id=1", [
      [1, $Options["Resource"], PdoInt]
    ]);
    if(count($result) > 0){
      $return = $SetPerms($return, $result[0]);
    }
    // Unauthenticated?
    if(isset($Options["User"]) == false or is_null($Options["User"])){
      return $return;
    }
    // Admin?
    $result = $PhpLivePdo->SQL("select *
      from sys_usergroup
      where group_id=3
        and user_id=?", [
      [1, $Options["User"], PdoInt]
    ]);
    if(count($result) == 1){
      return ["r" => 1, "w" => 1, "o" => 1];
    }
    // Others
    $result = $PhpLivePdo->SQL("select r,w,o
      from sys_perms
      where resource_id=:resource
        and(
          user_id=:user
          or group_id=2
          or group_id in (select group_id from sys_groups where user_id=:user)
        )
      order by r,w,o", [
      [":resource", $Options["Resource"], PdoInt],
      [":user", $Options["User"], PdoInt]
    ]);
    if(count($result) > 0){
      $return = $result[0];
    }

    return $return;
  }

  private function SetPerms($All, $Perms){
    if($All["r"] !== 0)
      $All["r"] = $Perms["r"];
    if($All["w"] !== 0)
      $All["w"] = $Perms["w"];
    if($All["o"] !== 0)
      $All["o"] = $Perms["o"];
    return $All;
  }
}