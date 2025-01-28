<?php

namespace Drupal\fresh_apples_reviews\Controller;

use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class StatusController {
  public function updateStatus(Request $request) {
    // Dekoduj JSON z żądania
    $data = json_decode($request->getContent(), TRUE);

    if (isset($data['submission_id']) && isset($data['status'])) {
      $submission_id = $data['submission_id'];
      $new_status = $data['status'];

      // Zaktualizuj wiersz w tabeli `webform_submission_data`
      $updated = \Drupal::database()->update('webform_submission_data')
        ->fields(['value' => $new_status])
        ->condition('sid', $submission_id)
        ->condition('name', 'status')
        ->execute();

         if ($updated) {
        $this->sendEmail($submission_id, $new_status);

        return new JsonResponse(['message' => 'Status updated successfully']);
      }
      else {
        // Create webform submission
        return new JsonResponse(['message' => 'Failed to update status'], 500);
      }
    }

    // Jeśli dane są niepoprawne
    return new JsonResponse(['message' => 'Invalid data'], 400);
  }

  private function sendEmail($submission_id, $new_status) {
    $mail_manager = \Drupal::service('plugin.manager.mail');
    $langcode = \Drupal::currentUser()->getPreferredLangcode();

    // Pobierz dane zgłoszenia z tabeli `webform_submission_data`
    $email = \Drupal::database()->select('webform_submission_data', 'wsd')
      ->fields('wsd', ['value'])
      ->condition('sid', $submission_id)
      ->condition('name', 'email') // Zakładamy, że pole `email` istnieje
      ->execute()
      ->fetchField();

    if ($email) {
      $params = [
        'submission_id' => $submission_id,
        'new_status' => $new_status,
      ];

      // Wyślij e-mail
      $mail_manager->mail('fresh_apples_reviews', 'status_update', $email, $langcode, $params);
    }

    if ($new_status === 'accepted') {
      $user_id = \Drupal::database()->select('webform_submission', 'ws')
        ->fields('ws', ['uid'])
        ->condition('sid', $submission_id)
        ->execute()
        ->fetchField();

      $user = User::load($user_id);
      $user->addRole('reviewer');
      $user->save();
    }


  }
}
