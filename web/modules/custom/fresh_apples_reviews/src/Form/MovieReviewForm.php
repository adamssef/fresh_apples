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
      '#title' => $this->t('Twoja ocena (1-10)'),
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

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Wyślij'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this_page_node = \Drupal::routeMatch()->getParameter('node');
    $movie_id = $this_page_node->id();
    $review = $form_state->getValue('review');
    $rating = $form_state->getValue('rating');

    // Create a new "Review" node or save to a custom table
    $node = Node::create([
      'type' => 'review',
      'title' => $this->t('Review for Movie @title', ['@title' => $$this_page_node->getTitle()]),
      'field_movie' => $movie_id, // Reference the movie
      'field_review_content' => $review,
      'field_review_rating' => $rating,
    ]);
    $node->save();

    \Drupal::messenger()->addMessage($this->t('Review submitted successfully!'));
  }
}
