<?php

    class Guestbook {

        public static function addSignature() {
            $nick = $_POST['nick'];

            if (!preg_match('/^[a-z0-9àáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ∂ð.\-\'_ ]+$/i', $nick)) {
                Messages::push(Messages::format('
                    <div class="notification error">
                        <div class="title">{Title}:</div>
                        <div class="body">{Body}</div>
                    </div>', [
                        'Title' => 'Error',
                        'Body' => 'Your name is empty or it contains illegal characters!'
                    ]));

                header('Location: /');
                return;
            } else if (strlen($nick) > 40) {
                Messages::push(Messages::format('
                    <div class="notification error">
                        <div class="title">{Title}:</div>
                        <div class="body">{Body}</div>
                    </div>', [
                        'Title' => 'Error',
                        'Body' => 'Your name is too long!'
                    ]));

                header('Location: /');
                return;
            }

            $query = Database::template('INSERT INTO signatures $ VALUES ?',
                                        ['nick', 'ip', 'time'],
                                        [$nick, $_SERVER['REMOTE_ADDR'], mktime()]);
            $result = Database::query($query);

            if (!$result->isError) {
                Messages::push(Messages::format('
                <div class="notification error">
                    <div class="title">{Title}:</div>
                    <div class="body">{Body}</div>
                </div>', [
                    'Title' => 'Success',
                    'Body' => "Thank you for adding your name to our guestbook, $nick!"
                ]));
            } else {
                Messages::push(Messages::format('
                <div class="notification error">
                    <div class="title">{Title}:</div>
                    <div class="body">{Body}</div>
                </div>', [
                    'Title' => 'Error',
                    'Body' => "It looks like you have already signed our guestbook"
                ]));
            }
    
            header('Location: /');
        }
        
    }

?>