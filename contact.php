<?php include 'header.php'; ?>

<h2>Contact Us</h2>

<form method="post">

    <label>Name</label><br>
    <input type="text" name="name" required><br><br>

    <label>Email</label><br>
    <input type="email" name="email" required><br><br>

    <label>Message</label><br>
    <textarea name="message" rows="5"></textarea><br><br>

    <button type="submit">Send</button>

</form>

<?php

if($_SERVER["REQUEST_METHOD"]=="POST"){

    $name=$_POST['name'];
    $email=$_POST['email'];

    echo "<h3>Thank You!</h3>";
    echo "Name : ".$name."<br>";
    echo "Email : ".$email;
}

?>

<?php include 'footer.php'; ?>