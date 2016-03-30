<html>
<head>
    <script type="text/javascript" src="jquery-1.12.2.min.js"></script>
    <script>
        $(document).ready( function() {

            $('#authModel, #authAction').on('change, click', function() {

                $('#blk_formFields > div').hide();

                var action = $('#authAction').val();
                var model = $('#authModel').val();
                $('.' + action + model).show();
            });

        });
    </script>
    <style>
        #blk_formFields > div {
            display: none;
        }
    </style>
</head>
<body>
<h1>Authorize.net sandbox</h1>
<form id="myform" method="post" action="testme.php">
    <div>
        <input type="reset" value="Reset" />
    </div>

    <div>
        <select id="authAction" name="authAction" size="4">
            <option value="add">Add</option>
            <option value="get">Get</option>
            <option value="update">Update</option>
            <option value="delete">Delete</option>
        </select>

        <select id="authModel" name="authModel" size="3">
            <option value="CustomerProfile">Customer Profile</option>
            <option value="CustomerPaymentProfile">Payment Profile</option>
            <option value="CustomerShippingProfile">Shipping Address</option>
        </select>
        <br />
    </div>

    <div id="blk_formFields">
        <div class="addCustomerProfile updateCustomerProfile">
            <fieldset>
                <legend>Profile Info</legend>
                Customer Id
                <input type="text" name="customerId" />
                <br /><br />
                Description
                <input type="text" name="description" />
                <br /><br />
                Email
                <input type="text" name="email" />
            </fieldset>
            <br />
        </div>

        <div class="addCustomerPaymentProfile addCustomerShippingProfile getCustomerProfile getCustomerPaymentProfile getCustomerShippingProfile updateCustomerProfile updateCustomerPaymentProfile updateCustomerShippingProfile deleteCustomerProfile deleteCustomerPaymentProfile deleteCustomerShippingProfile">
            <fieldset>
                <legend>Authorize.net Info</legend>
                Customer Profile ID
                <input type="text" name="profile_id" />
            </fieldset>
            <br />
        </div>

        <div class="getCustomerPaymentProfile updateCustomerPaymentProfile deleteCustomerPaymentProfile">
            <fieldset>
                <legend>Authorize.net Info</legend>
                Payment Profile ID
                <input type="text" name="payment_id" />
            </fieldset>
            <br />
        </div>

        <div class="getCustomerShippingProfile updateCustomerShippingProfile deleteCustomerShippingProfile">
            <fieldset>
                <legend>Authorize.net Info</legend>
                Shipping Profile ID
                <input type="text" name="shipping_id" />
            </fieldset>
            <br />
        </div>

        <div class="addCustomerPaymentProfile updateCustomerPaymentProfile">
            <fieldset>
                <legend>Credit Card Info</legend>
                Credit Card Number
                <input type="text" name="cc_number" maxlength="16" />
                <br /><br />
                Expiration Date
                <select name="cc_expiration_dd">
                    <option value="bad">Expired</option>
                    <option value="good" selected="selected">Not Expired</option>
                </select>
                <br /><br />
                Verification Code
                <input type="text" name="cc_code" maxlength="3"/>
            </fieldset>
            <br />
        </div>

        <div class="addCustomerPaymentProfile addCustomerShippingProfile updateCustomerPaymentProfile updateCustomerShippingProfile">
            <fieldset>
                <legend>Address Info</legend>
                Firstname
                <input type="text" name="firstname" />
                <br /><br />
                Lastname
                <input type="text" name="lastname" />
                <br /><br />
                Company
                <input type="text" name="company" />
                <br /><br />
                Address
                <input type="text" name="address" />
                <br /><br />
                City
                <input type="text" name="city" />
                <br /><br />
                State
                <input type="text" name="state" />
                <br /><br />
                Zip / Postal
                <input type="text" name="zip" />
                <br /><br />
                Phone
                <input type="text" name="phone" />
                <br /><br />
                Fax
                <input type="text" name="fax" />
                <br /><br />
                Country
                <input type="text" name="country" />
            </fieldset>
            <br />
        </div>
    </div>

    <div>
        <input type="submit" value="Submit" />
    </div>
</form>
</body>
</html>
