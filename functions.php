<?php

function getAllUsers(){
    global $conn;
    $sql = "SELECT user_id from users";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $array = array();
        while($row = $result->fetch_assoc()) {
            array_push($array, $row['user_id']);
        }
        return $array;
    } else {
        return false;
    }
}
function getPreviousCommand($user){
    global $conn;
    $sql = "SELECT previousCommand from users where user_id='".$user."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        return $row[0];
    } else {
        return false;
    }
}
function setPreviousCommand($user, $command){
    global $conn;
    $sql = "UPDATE users SET previousCommand='".$command."' WHERE user_id='".$user."'";
    if ($conn->query($sql) === TRUE) {
        return true;
    } else {
        return $conn->error;
    }
}

function getTeamName($user){
    global $conn;
    $sql = "SELECT teamName from users where user_id='".$user."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        return $row[0];
    } else {
        return false;
    }
}

function checkTeamCode($teamCode) {
    global $conn;
    $sql = "SELECT teamName from users where teamCode='".$teamCode."'";
    $result = $conn->query($sql);
    if ($result->num_rows == 1) {
        return $result->num_rows;
    } elseif ($result->num_rows > 1) {
        return $result->num_rows;
    } else {
        return false;
    }
}


function getTeamNameByCode($teamCode){
    global $conn;
    $sql = "SELECT teamName from users where teamCode='".$teamCode."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_row();
        return $row[0];
    } else {
        return false;
    }
}

function setTeamName($user, $teamName){
    $teamName = strval($teamName);
    global $conn;
    $sql = "UPDATE users SET teamName='".$teamName."' WHERE user_id='".$user."'";
    if ($conn->query($sql) === TRUE) {
        return true;
    } else {
        return $sql." ".$conn->error;
    }
}

function random_strings()
{
    $str_result = '1234567890abcdefghijklmnopqrstuvwxyz';
    return substr(str_shuffle($str_result), 0, 6);
}

function setTeamCode($user, $code) {
    global $conn;
    $sql = "UPDATE users SET teamCode='".$code."' WHERE user_id='" . $user . "'";
    if ($conn->query($sql) === TRUE) {
        return true;
    } else {
        return $conn->error;
    }
}

function getProfileInfo($user) {
    global $conn;
    $sql = "SELECT name, lastname, username, teamName, rate, step, user_id FROM users WHERE teamCode=(SELECT teamCode FROM users WHERE user_id='".$user."')";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $array = array(
            "me" => [],
            "friend" => [],
        );
        while($row = $result->fetch_assoc()) {
            if ($row["user_id"] == $user) {
                $array['me']['name'] = $row["name"];
                $array['me']['lastname'] = $row["lastname"];
                $array['me']['username'] = $row["username"];
                $array['me']['teamName'] = $row["teamName"];
                $array['me']['rate'] = intval($row["rate"]);
                $array['me']['step'] = intval($row["step"]);
            } else {
                $array['friend']['name'] = $row["name"];
                $array['friend']['lastname'] = $row["lastname"];
                $array['friend']['username'] = $row["username"];
                $array['friend']['rate'] = intval($row["rate"]);
                $array['friend']['step'] = intval($row["step"]);
            }
        }
        return $array;
    } else {
        return false;
    }
}

function setBlockStatus($user, $status) {
    global $conn;
    $sql = "UPDATE users SET bot_blocked='".$status."' WHERE user_id='" . $user . "'";
    if ($conn->query($sql) === TRUE) {
        return true;
    } else {
        return $conn->error;
    }
}
