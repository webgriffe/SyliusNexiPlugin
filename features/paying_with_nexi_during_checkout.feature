@paying_for_order
Feature: Paying with nexi during checkout
    In order to buy products
    As a Customer
    I want to be able to pay with Nexi Simple Payment Checkout

    Background:
        Given the store operates on a single channel in "United States"
        And there is a user "john@example.com" identified by "password123"
        And the store has a payment method "Nexi payment method" with a code "NEXI_PAYMENT_METHOD" and Nexi Simple Payment Checkout gateway
        And the store has a product "PHP T-Shirt" priced at "$19.99"
        And the store ships everywhere for free
        And I am logged in as "john@example.com"

    @ui
    Scenario: Send right data to Nexi during payment initiation
        Given I added product "PHP T-Shirt" to the cart
        And I have proceeded selecting "Nexi payment method" payment method
        When I confirm my order
        Then I should see be successfully redirected to Nexi payment gateway

    @ui @javascript
    Scenario: Successful payment
        Given I added product "PHP T-Shirt" to the cart
        And I have proceeded selecting "Nexi payment method" payment method
        When I confirm my order
        And I complete the payment on Nexi
        Then I should be on the waiting payment processing page
        When Nexi notify the store about the successful payment
        Then I should be redirected to the thank you page
        And I should be notified that my payment has been completed
        When I am viewing the summary of my last order
        Then I should see its payment status as "Completed"

    @ui @javascript
    Scenario: Failed payment
        Given I added product "PHP T-Shirt" to the cart
        And I have proceeded selecting "Nexi payment method" payment method
        When I confirm my order
        And I complete the payment on Nexi
        Then I should be on the waiting payment processing page
        When Nexi notify the store about the failed payment
        Then I should be redirected to the order page
        And I should be notified that my payment is failed
        And I should be able to pay again

    @ui @javascript
    Scenario: Cancelling the payment
        Given I added product "PHP T-Shirt" to the cart
        And I have proceeded selecting "Nexi payment method" payment method
        When I confirm my order
        And I cancel the payment on Nexi
        Then I should be on the waiting payment processing page
        When Nexi notify the store about the cancelled payment
        Then I should be redirected to the order page
        And I should be notified that my payment has been cancelled
        And I should be able to pay again

    @ui @javascript
    Scenario: Retrying the payment with success
        Given I added product "PHP T-Shirt" to the cart
        And I have proceeded selecting "Nexi payment method" payment method
        And I have confirmed order
        But I have cancelled Nexi payment
        And Nexi notify the store about the cancelled payment
        Then I should be redirected to the order page
        When I try to pay again with Nexi
        And Nexi notify the store about the successful payment
        Then I should be redirected to the thank you page
        And I should be notified that my payment has been completed

    @ui
    Scenario: Successful payment even without returning to the store
        Given I added product "PHP T-Shirt" to the cart
        And I have proceeded selecting "Nexi payment method" payment method
        When I confirm my order
        And I complete the payment on Nexi without returning to the store
        And Nexi notify the store about the successful payment
        And I am viewing the summary of my last order
        Then I should see its payment status as "Completed"
