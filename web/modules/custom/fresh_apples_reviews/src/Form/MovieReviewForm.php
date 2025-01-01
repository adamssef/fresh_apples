<?php

namespace Drupal\fresh_apples_reviews\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\fresh_apples_show_page\Service\FreshApplesShowPageService;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MovieReviewForm extends FormBase {

  protected FreshApplesShowPageService $freshApplesShowPageService;

  /**
   * Constructs a new MovieReviewForm.
   *
   * @param FreshApplesShowPageService $fresh_apples_show_page_service
   *   The site path.
   */
  public function __construct(
    protected FreshApplesShowPageService $fresh_apples_show_page_service,
  ) {
    $this->freshApplesShowPageService = $fresh_apples_show_page_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('fresh_apples_show_page.service'),
    );
  }

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

    $currentUserId = \Drupal::currentUser()->id();
    $this_page_node = \Drupal::routeMatch()->getParameter('node');

    if ($this->fresh_apples_show_page_service->getTheReviewByUidAndShowId($currentUserId, $this_page_node->id())) {
      $prev_rating = $this->fresh_apples_show_page_service->getPrevRating($this_page_node);

      if ($prev_rating) {

        if ($prev_rating) {
          $form['rating']['#default_value'] = $prev_rating;
        }
      }
    }
    else {
      $form['review_title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Tytuł'),
        '#required' => FALSE,
      ];
      $form['review'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Recenzja (opcjonalnie)'),
        '#required' => FALSE,
      ];
    }

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
    $title = $form_state->getValue('review_title');
    $current_user_roles = \Drupal::currentUser()->getRoles();
    $is_reviewer = in_array('reviewer', $current_user_roles);

    // Create a new "Review" node or save to a custom table
    $node = Node::create([
      'type' => 'review',
      'title' => $title ?? $this->t('Review for Movie @title', ['@title' => $this_page_node->getTitle()]),
      'field_show' => $movie_id, // Reference the movie
      'field_review_content' => $review,
      'field_review_rating' => $rating,
      'field_is_from_ciritc' => $is_reviewer,
    ]);
    $node->save();

    \Drupal::messenger()->addMessage($this->t('Review submitted successfully!'));
  }
}
