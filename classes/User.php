<?php

    /*
    include       -include again and again every time you refresh the page
    include_once  -inlude only once

    require       -require again and again and will stop the script when there's error
    require_once  -require once only and will stop the script when there's error
    */


    require_once "Database.php";

    class User extends Database
    {
        // store()  -  Insert record
        public function store($request)
        {
            // Final Value
            $first_name = $request['first_name'];
            $last_name  = $request['last_name'];
            $username   = $request['username'];
            $password   = $request['password'];

            $password = password_hash($password, PASSWORD_DEFAULT); //DBのパスワードを暗号化する

            #DBの内容　it should be the same order
            $sql = "INSERT INTO users (first_name, last_name, username, password) VALUES ('$first_name', '$last_name', '$username', '$password') ";

            if($this->conn->query($sql)){
                header('location: ../views'); // after successfully data is collected, go to index.php or the login page
                exit;                         // same as die
            } else {
                die('Error creating the user: ' . $this->conn->error);
            }
        }

        public function login($request)
        {
            $username = $request['username'];
            $password = $request['password'];

            $sql = "SELECT * FROM users WHERE username = '$username'";

            $result = $this->conn->query($sql);

            #CHECK the username exists
            if($result->num_rows == 1){
                # CHECK if the password is correct
                $user = $result->fetch_assoc();
                                // to get the associative allay like below
                // $user = ['id' => 1, 'first_name'=> 'Kika', 'last_name' => 'Kurokawa', 'username' => 'kika', 'password' => '$2y$10$GX', 'photo' => null ]    
                
                if(password_verify($password, $user['password'])){
                    #CREATE the sessiom variables
                    session_start(); // to start the SESSION below

                    $_SESSION['id']          = $user['id'];  // 1
                    $_SESSION['username']    = $user['username']; // 'kika'
                    $_SESSION['full_name']   = $user['first_name'] . " " . $user['last_name']; // 'Kika Kurokawa'

                    header('location: ../views/dashboard.php');
                    exit; // exits the login page = clear everything

                } else {
                    die('Password is incorrect');
                }
            } else {
                die('Username not found');
            }
        }


        public function logout()
        {
            session_start();
            session_unset(); //removeing the values inside
            session_destroy();

            header('location: ../views'); //login page
            exit;
        }

        public function getAllUsers()
        {
            $sql = "SELECT id, first_name, last_name, username, photo FROM users"; 

            if($result = $this->conn->query($sql)){
                return $result;
            } else {
                die('ERROR in retrieving all users: ' . $this->conn->error);
            }
        }

        public function getUser($id)
        {
            $sql = "SELECT * FROM users WHERE id = $id";

            if($result = $this->conn->query($sql)){
                return $result->fetch_assoc(); //get one person's information
                // ['id' => 23, 'first_name' => 'Dan', 'last_name' => 'Smith', 'username' => 'Dan']
            } else {
                die('Error in retrieving the user: ' . $this->conn->error);
            }
        }

        public function update($request, $files)
        {
            session_start();  // need this when using $_SESSION

            $id             = $_SESSION['id'];
            $first_name     = $request['first_name'];
            $last_name      = $request['last_name'];
            $username       = $request['username'];
            $photo          = $files['photo']['name']; // woman1.jpg // holds the name of the image
            $tmp_photo      = $files['photo']['tmp_name']; // holds the image from the temporary storage
            // ['photo']    is the name of the form  <input ... name="photo"> from edit-user.php (views)
            // ['name']     is the name of the image
            // ['tmp_name'] is the temporary storage (not visible) of the image 

            $sql = "UPDATE users
                    SET first_name = '$first_name',
                        last_name = '$last_name',
                        username = '$username'
                    WHERE id = '$id'";

            if($this->conn->query($sql)){
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = "$first_name $last_name";

                #IF there is an updated photo, save it to the database and save the files to the images folder
                if($photo){ // if there is a new photo...
                    $sql = "UPDATE users SET photo = '$photo' WHERE id = '$id'"; // save the name of the photo first
                    $destination = "../assets/images/$photo"; // save in images folder

                    // SAVE the image name to the DB
                    if($this->conn->query($sql)){
                        // SAVE the file to the images folder
                        if(move_uploaded_file($tmp_photo, $destination)){ // move the photo from temporary storage to actual images file ($tmp_photo => $destination)
                            header('location: ../views/dashboard.php');
                            exit;
                        } else {
                            die('ERROR in moving the photo.');
                        }
                    } else {
                        die('ERROR in uploading the photo: ' . $this->conn->error);
                    }
                }

                header('location: ../views/dashboard.php');
                exit;
            } else {
                die('ERROR in updating the user: ' . $this->conn->error);
            }
        }

        public function delete()
        {
            session_start();
            $id = $_SESSION['id']; // the user currently logged in

            $sql = "DELETE FROM users WHERE id = '$id'"; // delete the user

            if($this->conn->query($sql)){
                $this->logout(); // the same one above
            } else {
                die("ERROR in deleting your account: " . $this->conn->error);
            }
        }
    }

?>