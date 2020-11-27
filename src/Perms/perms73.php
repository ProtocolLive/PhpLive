<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020.11.27.00

class PhpLivePerms{
  private $PhpLivePdo;

  public function __construct(PhpLivePdo &$PhpLivePdo){
    $this->PhpLivePdo = $PhpLivePdo;
  }

  public function Access(array $Options){
    if($this->PhpLivePdo === null):
      return false;
    endif;
    $Options['User'] = $Options['User'] ?? null;

    $return = ['r' => null, 'w' => null, 'o' => null];
    //Get resource id by name
    if(is_numeric($Options['Resource']) === false):
      $result[1] = [
        [':resource', $Options['Resource'], PdoStr]
      ];
      if(session_name() === 'PHPSESSID'):
        $result[0] = 'site is null';
      else:
        $result[0] = 'site=:site';
        $result[1][] = [':site', session_name(), PdoStr];
      endif;
      $result = $this->PhpLivePdo->Run("
        select resource_id
        from sys_resources
        where resource=:resource
          and $result[0]
      ",
        $result[1]
      );
      if(count($result) === 0):
        return false;
      else:
        $Options['Resource'] = $result[0][0];
      endif;
    endif;
    // Permissions for everyone
    $result = $this->PhpLivePdo->Run("
      select r,w,o
      from sys_perms
      where resource_id=?
        and group_id=1
    ",[
      [1, $Options['Resource'], PdoInt]
    ]);
    if(count($result) > 0):
      $return = $this->SetPerms($return, $result[0]);
    endif;
    // Unauthenticated?
    if($Options['User'] === null):
      return $return;
    endif;
    // Admin?
    $result = $this->PhpLivePdo->Run("
      select *
      from sys_usergroup
      where group_id=3
        and user_id=?
    ",[
      [1, $Options['User'], PdoInt]
    ]);
    if(count($result) === 1):
      return ['r' => true, 'w' => true, 'o' => true];
    endif;
    // Others
    $result = $this->PhpLivePdo->Run("
      select r,w,o
      from sys_perms
      where resource_id=:resource
        and(
          user_id=:user
          or group_id=2
          or group_id in (select group_id from sys_groups where user_id=:user)
        )
      order by r,w,o
    ",[
      [':resource', $Options['Resource'], PdoInt],
      [':user', $Options['User'], PdoInt]
    ]);
    if(count($result) > 0):
      $return = $result[0];
    endif;
    return $return;
  }

  private function SetPerms(array $All, array $Perms):array{
    if($All['r'] !== false):
      $All['r'] = $Perms['r'];
    endif;
    if($All['w'] !== false):
      $All['w'] = $Perms['w'];
    endif;
    if($All['o'] !== false):
      $All['o'] = $Perms['o'];
    endif;
    return $All;
  }
}