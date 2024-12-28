<?php

namespace Drupal\fresh_apples_reviews\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class MovieReviewForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'movie_review_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $movie_id = NULL) {
    $form['rating'] = [
      '#type' => 'number',
      '#title' => $this->t('Rating (1-10)'),
      '#min' => 1,
      '#max' => 10,
      '#required' => TRUE,
    ];

    $form['review'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Recenzja (opcjonalnie)'),
      '#required' => FALSE,
    ];

    $user = \Drupal::currentUser();
    $roles = $user->getRoles();

    if (in_array('reviewer', $roles)) {
      $form['is_critics_review'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Czy to recenzja krytyka?'),
        '#required' => FALSE,
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Oceń to!'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $movie_id = $form_state->getValue('movie_id');
    $review = $form_state->getValue('review');
    $rating = $form_state->getValue('rating');

    // Create a new "Review" node or save to a custom table
    $node = Node::create([
      'type' => 'review',
      'title' => $this->t('Review for Movie @id', ['@id' => $movie_id]),
      'field_movie' => $movie_id, // Reference the movie
      'field_review' => $review,
      'field_rating' => $rating,
    ]);
    $node->save();

    \Drupal::messenger()->addMessage($this->t('Review submitted successfully!'));
  }
}
