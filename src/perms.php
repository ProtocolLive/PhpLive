<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020-01-30-00

function Access($Resource, $User = null){
  if(is_numeric($Resource) == false){
    $result = SQL("select resource_id from sys_resources where resource=?", [
      [1, $Resource, PDO::PARAM_STR]
    ]);
    $Resource = $result[0][0];
  }
  if($User == null){
    $result = SQL("select r,w,o from sys_perms where group_id=1 and resource_id=?", [
      [1, $Resource, PDO::PARAM_INT]
    ]);
    if(count($result) > 0){
      return $result;
    }else{
      return ["r" => 0, "w" => 0, "o" => 0];
    }
  }else{
    $result = SQL("select r,w,o from sys_perms where group_id=3 and user_id=?", [
      [1, $User, PDO::PARAM_INT]
    ]);
    if(count($result) > 0){
      $return = ["r" => 1, "w" => 1, "o" => 1];
    }else{
      $result = SQL("select r,w,o
        from sys_perms
        where resource_id=:resource
          and(
            user_id=:user
            or group_id=2
            or group_id in (select group_id from sys_groups where user_id=:user)
          )
        order by r,w,o", [
        [":resource", $Resource, PDO::PARAM_INT],
        [":user", $User, PDO::PARAM_INT]
      ]);
      if(count($result) > 0){
        return $result[0];
      }else{
        return ["r" => 0, "w" => 0, "o" => 0];
      }
    }
  }
}