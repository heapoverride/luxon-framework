<div class="container">
    <h1>Guestbook</h1>

    <h3>Sign your name to our guestbook</h3>

    <?php
        foreach (Messages::flush() as $msg) {
            echo $msg;
        }
    ?>

    <form method="POST">
        <input type="text" name="nick" class="text-input" autocomplete="off" placeholder="Your name here">
        <input type="submit" class="button" value="Sign">
    </form>

    <h3>Signatures in our guestbook</h3>

    <?php
        $signatures = Signature::getAll();

        foreach ($signatures as $signature) {
            echo Messages::format('<span class="signature">{Nick}</span>', [
                'Nick' => $signature->nick
            ]);
        }

        if (count($signatures) == 0) {
            echo 'Nobody has signed their name to this guestbook yet...';
        }
    ?>

</div>