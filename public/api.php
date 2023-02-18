<?php
error_reporting(E_ALL);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Selective\BasePath\BasePathMiddleware;
use Slim\Factory\AppFactory;
use Slim\Http\Factory\DecoratedResponseFactory;
use App\Classes\Email;
use App\Classes\CustomPdfGenerator;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->addRoutingMiddleware();
$app->add(new BasePathMiddleware($app));
$app->addErrorMiddleware(true, true, true);

$app->get('/', function (Request $request, Response $response) {
   $response->getBody()->write('Hello World!');
   return $response;
});

$app->get('/api/artikel', function (Request $request, Response $response) {

    $artikelJson = file_get_contents("../src/database/merch.json");
    $artikel = json_decode($artikelJson, true);

    return $response->withJson($artikel, 200, JSON_PRETTY_PRINT);

 });

 $app->post('/api/checkout', function (Request $request, Response $response) {
   $postDataAsArray = $request->getParsedBody();
   $apiKey = $postDataAsArray['apiKey'];

   //todo put api key in .env file
   if($apiKey !== 'erthj2rAsdgv$|4KL') {
      $responseBody = json_encode(['status' => 403, 'message' => 'apiKey not found']);
      $response = new Slim\Psr7\Response($status=500);
      $response->getBody()->write($responseBody);
      $response = $response->withHeader('Content-Type', 'application/json');
      return $response;
   }

   $pdfSaveLocation = __DIR__ . '/'.'invoices';

   $pdfData = array();

   $postArtikel = $postDataAsArray['artikel'];

   //static customerData for example
   $anrede = 'Herrn';
   $nachname = 'Friebe';
   $postleitzahl = 12345;
   $strasse = 'Teststraße';
   $hausnummer = '23';
   $ort= 'Testort';
   $email = 'test@test.de';

   $pdfData['customerData'] = array(
      'anrede' => $anrede,
      'nachname' => $nachname,
      'strasse' => $strasse,
      'postleitzahl' => $postleitzahl,
      'hausnummer' => $hausnummer,
      'ort' => $ort,
      'email' => $email
   );

   
   //searching for artikelid in json file to make price calculation
   $artikelJson = file_get_contents("../src/database/merch.json");
   $alleArtikel = json_decode($artikelJson);

   //filter and match articles from request to json/db articles to make price calulation 
   foreach($postArtikel as $artikel) {
      $filtered_arr = filter($alleArtikel, (int)$artikel['id']);

      if($filtered_arr === null) {
         $pdfData['artikelData'] = [];
         break;
      }


      $pdfData['artikelData'][] = array(
         'Artikel #' . $filtered_arr->id . ' ('.  $filtered_arr->headline . ')',
         $filtered_arr->size,
         $artikel['anzahl'],
         $filtered_arr->price,
         $filtered_arr->price * $artikel['anzahl']
      );
   }


   //set some static pdf data
   $dtZone = new DateTimeZone('Europe/Berlin');
   $rechnungsDatum = new DateTime('now', $dtZone);
   $pdfData['invoiceNumber'] = rand(3, 100);
   $pdfData['pdfName'] = $rechnungsDatum->format('Ymd') . '-' . $pdfData['invoiceNumber'];
   $pdfData['pdfSaveLocation'] = $pdfSaveLocation;



   //pdf generation start
   $isPdfGenerated = generatePdf($pdfData);

   if(!$isPdfGenerated) {
      $responseBody = json_encode(['status' => 500, 'message' => 'fehler beim generieren der rechnung']);
      $response = new Slim\Psr7\Response($status=500);
      $response->getBody()->write($responseBody);
      $response = $response->withHeader('Content-Type', 'application/json');
      return $response;
   }

   //check if invoice file was generated and saved in folder
   if(!file_exists($pdfData['pdfSaveLocation'] . '/' . $pdfData['pdfName'] . '.pdf')) {
      $responseBody = json_encode(['status' => 500, 'message' => 'nicht erfolgreich']);
      $response = new Slim\Psr7\Response($status=500);
      $response->getBody()->write($responseBody);
      $response = $response->withHeader('Content-Type', 'application/json');
      return $response;
   }

   $email = new Email();

   // $isEmailSend = $email->sendMail(
   //    $pdfData['pdfSaveLocation'] . '/' . $pdfData['pdfName'] . '.pdf',
   //    'myemail@test.de'
   // );

   //for develop purpose set isEmailSend to false
   $isEmailSend = false;


   if(!$isEmailSend) {
      $responseBody = json_encode(['status' => 500, 'message' => 'email konnte nicht versendet werden']);
      $response = new Slim\Psr7\Response($status=500);
      $response->getBody()->write($responseBody);
      $response = $response->withHeader('Content-Type', 'application/json');
      return $response;
   }

   $responseBody = json_encode(['status' => 200, 'message' => 'erfolgreich']);
   $response = new Slim\Psr7\Response($status=500);
   $response->getBody()->write($responseBody);
   $response = $response->withHeader('Content-Type', 'application/json');
   return $response;

   //TODO  1 send success email to customer "Your order was received"
   //TODO  2 send mail to shop owner with generated invoice as attachment and body text with further information
});

function generatePdf(array $pdfData): bool {
   $pdf = new CustomPdfGenerator(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
   $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
   $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
   $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
   $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
   $pdf->setFontSubsetting(true);
   $pdf->SetFont('helvetica', '', 12, '', true);
   
   // start a new page
   $pdf->AddPage();
   
   // bill to
   $pdf->writeHTML($pdfData['customerData']['anrede'] . ' ' . $pdfData['customerData']['nachname'], true, false, false, false, 'L');
   $pdf->writeHTML($pdfData['customerData']['strasse'] . ' ' . $pdfData['customerData']['hausnummer'], true, false, false, false, 'L');
   $pdf->writeHTML($pdfData['customerData']['postleitzahl'] . ' ' . $pdfData['customerData']['ort'], true, false, false, false, 'L');
   $pdf->Write(0, "\n", '', 0, 'C', true, 0, false, false, 0);

   // date and invoice no
   $pdf->Write(0, "\n", '', 0, 'C', true, 0, false, false, 0);
   $pdf->writeHTML("Rechnungsnummer: #" . $pdfData['invoiceNumber'], true, false, false, false, 'R');

   $dtZone = new DateTimeZone('Europe/Berlin');
   $rechnungsDatum = new DateTime('now', $dtZone);
   $lieferDatum = new DateTime('now', $dtZone);
   $lieferDatum->add(new DateInterval('P5D'));
   $pdf->writeHTML("Rechnungsdatum: " . $rechnungsDatum->format('d.m.Y'), true, false, false, false, 'R');
   $pdf->writeHTML("Lieferdatum: " . $lieferDatum->format('d.m.Y'), true, false, false, false, 'R');
   $pdf->Write(0, "\n", '', 0, 'C', true, 0, false, false, 0);
   
   // Headline 
   $pdf->writeHTML("<h1>Rechnung</h1>", true, false, false, false, 'L');
   $pdf->Write(0, "\n", '', 0, 'C', true, 0, false, false, 0);
   $pdf->Ln();
   
   // invoice table starts here
   $header = array('Beschreibung', 'Größe', 'Menge', 'Einzelpreis', 'Gesamtpreis');
   $pdf->printTable($header, $pdfData['artikelData']);
   $pdf->Ln();
   
   // comments
   $pdf->SetFont('', '', 12);
   $pdf->writeHTML("<b>OTHER COMMENTS:</b>");
   $pdf->writeHTML("Method of payment: <i>We accept PAYPAL or Bank transfer</i>");
   $pdf->writeHTML("PayPal ID: <i>test@paypal.com");
   $pdf->Write(0, "\n\n\n", '', 0, 'C', true, 0, false, false, 0);
   $pdf->writeHTML("If you have any questions about this invoice, please contact:", true, false, false, false, 'C');
   $pdf->writeHTML("test@gmail.com", true, false, false, false, 'C');
   

   //FI for save and display inline example for test with postman
   try {
      $pdf->Output($pdfData['pdfSaveLocation'] . '/'. $pdfData['pdfName'] . '.pdf', 'F');
      return true;
   } catch (e) {
      return true;
   }
}

$app->run();

function filter ($alleArtikel, $id) {
   return array_values(array_filter(
      $alleArtikel,

      function($obj) use($id){ 
        return $obj->id === $id;
      }
   ))[0] ?? null;
}
