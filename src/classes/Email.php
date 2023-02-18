<?php
namespace App\Classes;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Email {

    public function sendMail
    (
        $invoicePDF,
        $toEmailaddress,
    ) 
    {
        $mail = new PHPMailer(true);

        //Enable SMTP debugging.
        //$mail->SMTPDebug = 3;   

        //Set PHPMailer to use SMTP.
        $mail->isSMTP();            
        //Set SMTP host name                          
        $mail->Host = "host";
        //Set this to true if SMTP host requires authentication to send email
        $mail->SMTPAuth = true;                          
        //Provide username and password     
        $mail->Username = "username";                 
        $mail->Password = "password";                           
        //If SMTP requires TLS encryption then set it
        $mail->SMTPSecure = "tls";                           
        //Set TCP port to connect to
        $mail->Port = 587;                                   
    
        $mail->From = "from";
        $mail->FromName = "Testname";
    
        $mail->addAddress($toEmailaddress, "Customername");
    
        $mail->isHTML(true);
    
        $mail->Subject = "Subject Text";
        $mail->Body = "<i>Mail body in HTML</i>";
        $mail->AddAttachment($invoicePDF, $name="rechnung.pdf", $encoding = 'base64', $type = 'application/pdf');
        $mail->AltBody = "This is the plain text version of the email content";
    
        if (!$mail->Send()) {
            return false;
        } else {
            return true;
        }
    }
}