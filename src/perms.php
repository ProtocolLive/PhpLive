<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020.05.26.00

class PhpLivePerms{
  private ?PhpLivePdo $PhpLivePdo = null;

  public function __construct(PhpLivePdo &$PhpLivePdo = null){
    $this->PhpLivePdo = $PhpLivePdo;
  }

  public function Access(array $Options):array{
    if($this->PhpLivePdo === null):
      if(isset($Options['PhpLivePdo']) == false):
        return false;
      else:
        $PhpLivePdo = &$Options['PhpLivePdo'];
      endif;
    else:
      $PhpLivePdo = $this->PhpLivePdo;
    endif;

    $return = ['r' => null, 'w' => null, 'o' => null];
    //Get resource id by name
    if(is_numeric($Options['Resource']) == false):
      if(session_name() == 'PHPSESSID'):
        $result = $PhpLivePdo->Run('select resource_id
          from sys_resources
          where resource=?
            and site is null', [
          [1, $Options['Resource'], PdoStr]
        ]);
      else:
        $result = $PhpLivePdo->Run('select resource_id
          from sys_resources
          where resource=?
            and site=?', [
          [1, $Options['Resource'], PdoStr],
          [2, session_name(), PdoStr]
        ]);
      endif;
      $Options['Resource'] = $result[0][0];
    endif;
    // Permissions for everyone
    $result = $PhpLivePdo->Run('select r,w,o
      from sys_perms
      where resource_id=?
        and group_id=1', [
      [1, $Options['Resource'], PdoInt]
    ]);
    if(count($result) > 0):
      $return = $this->SetPerms($return, $result[0]);
    endif;
    // Unauthenticated?
    if(isset($Options['User']) == false or is_null($Options['User'])):
      return $return;
    endif;
    // Admin?
    $result = $PhpLivePdo->Run('select *
      from sys_usergroup
      where group_id=3
        and user_id=?', [
      [1, $Options['User'], PdoInt]
    ]);
    if(count($result) == 1):
      return ['r' => 1, 'w' => 1, 'o' => 1];
    endif;
    // Others
    $result = $PhpLivePdo->Run('select r,w,o
      from sys_perms
      where resource_id=:resource
        and(
          user_id=:user
          or group_id=2
          or group_id in (select group_id from sys_groups where user_id=:user)
        )
      order by r,w,o', [
      [':resource', $Options['Resource'], PdoInt],
      [':user', $Options['User'], PdoInt]
    ]);
    if(count($result) > 0):
      $return = $result[0];
    endif;

    return $return;
  }

  private function SetPerms(array $All, array $Perms):array{
    if($All['r'] !== 0):
      $All['r'] = $Perms['r'];
    endif;
    if($All['w'] !== 0):
      $All['w'] = $Perms['w'];
    endif;
    if($All['o'] !== 0):
      $All['o'] = $Perms['o'];
    endif;
    return $All;
  }
}