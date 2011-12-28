<?php 
/**
 * django auth backend
 *
 * Uses external Trust mechanism to check against a django session id
 *
 * @author    Andreas Gohr <andi@splitbrain.org>
 * @author    Michael Luggen <michael.luggen at unifr.ch>
 * @author    Simon Sarazin <simonsarazin at imaginationforpeople dot org>
 * @author    Guillaume Libersat <guillaumelibersat at imaginationforpeople dot org>
 */

define('DOKU_AUTH', dirname(__FILE__));
define('AUTH_USERFILE', DOKU_CONF.'users.auth.php');

class auth_django extends auth_basic {
    var $dbh = null;
     /**
      * Constructor.
      *
      * Sets additional capabilities and config strings
      * @author    Michael Luggen <michael.luggen at rhone.ch>
      */

    function auth_django() {
        global $conf;
        
        // What is allowed with this backend
        $this->cando['external'] = true;
        $this->cando['getGroups'] = true;
        $this->cando['logout'] = false;

        // Connecting, selecting database
        try {
            $this->dbh = new PDO($conf['auth']['django']['dsn'], $conf['auth']['django']['user'], $conf['auth']['django']['password']);
        } catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }
    }

    /**
     * Just checks against the django sessionid variable
     */
   function trustExternal($user, $pass, $sticky=false) {
    
        global $USERINFO;
        global $conf;
        
        $sticky ? $sticky = true : $sticky = false; // sanity check
           
        if( isset($_COOKIE['sessionid']) ) {
            /**
             * get user info from django-database
             */

             $s_id =  $_COOKIE['sessionid'];

             // Connecting, selecting database
             // Look the cookie up in the db
             $sth = $this->dbh->query('SELECT session_data FROM django_session where session_key=' . $this->dbh->quote($s_id) . ' limit 1;');
             $obj = $sth->fetch(PDO::FETCH_ASSOC);
            
             $session_data = str_replace("\n", '', $obj['session_data']);


             // decrypting the session_data
             // XXX: Unpickling relies on executing a python process, yerk!
             $python_cmd = 'python -c "import base64, cPickle; val = base64.decodestring(\\"' . $session_data . '\"); print cPickle.loads(val[val.index(\':\')+1:])[\'_auth_user_id\'];"';
             exec($python_cmd, $output);
             $userid = $output[0];

             $sth = $this->dbh->query('SELECT username, first_name, last_name, email FROM auth_user where id=' . $this->dbh->quote($userid) . ' limit 1;');
             if ( $sth == FALSE )
                return false;
                
             $row = $sth->fetch(PDO::FETCH_ASSOC);

             $username =  $row['username'];
             $userfullname = $row['first_name']." ".$row['last_name'];
             $useremail = $row['email'];

             // okay we're logged in - set the globals
             
             if ( $groups = $this->_getUserGroups($username) )
                 array_push($groups, 'user');
             else
                 $groups[0] = 'user';

             $USERINFO['name'] = $userfullname;
             $USERINFO['pass'] = '';
             $USERINFO['mail'] = $useremail;
             $USERINFO['grps'] = $groups;

             $_SERVER['REMOTE_USER'] = $username;
             $_SESSION[DOKU_COOKIE]['auth']['user'] = $username;
             $_SESSION[DOKU_COOKIE]['auth']['info'] = $USERINFO;

             return true;
        }

        return false;
    }

    function _getUserGroups($user) {
        $sth = $this->dbh->query('SELECT auth_group.name FROM auth_user, auth_user_groups, auth_group where auth_user.username=' . $this->dbh->quote($user) . ' AND auth_user.id = auth_user_groups.user_id AND auth_user_groups.group_id = auth_group.id;');
        $a = 0;
        
        while($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $groups[$a] = $row['name'];
            $a++;
        };
        
        return $groups;
    }

    function retrieveGroups($start=0, $limit=0){
        $sth = $this->dbh->query('SELECT auth_group.name FROM auth_group');
        $a = 0;
        while($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $groups[$a] = $row[0];
            $a++;
        };
        
        return $groups;
    }

}

//Setup VIM: ex: et ts=4 :
