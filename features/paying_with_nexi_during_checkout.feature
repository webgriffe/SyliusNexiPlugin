@paying_for_order
Feature: Paying with nexi during checkout
    In order to buy products
    As a Customer
    I want to be able to pay with Nexi Simple Payment Checkout

    Background:
        Given the store operates on a single channel in "United States"
        And there is a user "john@example.com" identified by "password123"
        And the store has a payment method "Nexi" with a code "NEXI" and Nexi Simple Payment Checkout gateway
        And the store has a product "PHP T-Shirt" priced at "$19.99"
        And the store ships everywhere for free
        And I am logged in as "john@example.com"

    @ui @javascript
    Scenario: Successful payment
        Given I added product "PHP T-Shirt" to the cart
        And I have proceeded selecting "Nexi" payment method
        When I confirm my order with nexi payment
        And I sign in to PayPal and pay successfully
        Then I should be notified that my payment has been completed
        And I should see the thank you page
        And the latest order should have a payment with state "completed"

    Scenario: Cancelling the payment
        Given I added product "PHP T-Shirt" to the cart
        And I have proceeded selecting "Nexi" payment method
        When I confirm my order with paypal payment
        And I cancel my PayPal payment
        Then I should be notified that my payment has been cancelled
        And I should be able to pay again

    Scenario: Retrying the payment with success
        Given I added product "PHP T-Shirt" to the cart
        And I have proceeded selecting "Nexi" payment method
        And I have confirmed my order with paypal payment
        But I have cancelled PayPal payment
        When I try to pay again
        And I sign in to PayPal and pay successfully
        Then I should be notified that my payment has been completed
        And I should see the thank you page

    Scenario: Retrying the payment and failing
        Given I added product "PHP T-Shirt" to the cart
        And I have proceeded selecting "Nexi" payment method
        And I have confirmed my order with paypal payment
        But I have cancelled PayPal payment
        When I try to pay again
        And I cancel my PayPal payment
        Then I should be notified that my payment has been cancelled
        And I should be able to pay again
