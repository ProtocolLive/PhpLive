<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020-03-24-00

class PhpLivePerms{
  private $PhpLivePdo = null;

  public function __construct(&$PhpLivePdo = null){
    $this->PhpLivePdo = $PhpLivePdo;
  }

  public function Access($Resource, $User){
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
    if(is_numeric($Resource) == false){
      if(session_name() == "PHPSESSID"){
        $result = $PhpLivePdo->SQL("select resource_id
          from sys_resources
          where resource=?
            and site is null", [
          [1, $Resource, PdoStr]
        ]);
      }else{
        $result = $PhpLivePdo->SQL("select resource_id
          from sys_resources
          where resource=?
            and site=?", [
          [1, $Resource, PdoStr],
          [2, session_name(), PdoStr]
        ]);
      }
      $Resource = $result[0][0];
    }
    // Permissions for everyone
    $result = $PhpLivePdo->SQL("select r,w,o
      from sys_perms
      where resource_id=?
        and group_id=1", [
      [1, $Resource, PdoInt]
    ]);
    if(count($result) > 0){
      $return = $SetPerms($return, $result[0]);
    }
    // Unauthenticated?
    if($User == null){
      return $return;
    }
    // Admin?
    $result = $PhpLivePdo->SQL("select *
      from sys_usergroup
      where group_id=3
        and user_id=?", [
      [1, $User, PdoInt]
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
      [":resource", $Resource, PdoInt],
      [":user", $User, PdoInt]
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