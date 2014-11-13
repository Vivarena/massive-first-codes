    (function(){
        $(function(){
            $(".remove").live("click", function(){
                ajaxProcessStart();
                var id = $(this).attr("href");

                $.getJSON("/shop/del_item/" + id,
                            function(data)
                            {
                                if(data != "false")
                                {
                                    if(data.Gift){
                                        var giftId = data.Gift + ".0";
                                        $(".cart_item_" + giftId.replace(".", "\\.")).remove();
                                    }
                                    $(".cart_item_" + id.replace(".", "\\.")).remove();
                                    updateCart(data);
                                }
                                ajaxProcessStop();
                            }
                        )

                return false;
            })

            $("#charity").live('change', function(){


                var productId = $(this).attr("name");
                var value = $(this).val();

                $.ajax({
                    url: '/shop/ajaxSetCharity',
                    type: 'post',
                    data: {
                        productId: productId,
                        value: value
                    },
                    dataType: 'json',
                    beforeSend: ajaxProcessStart,
                    complete: ajaxProcessStop,
                    success: function(response) {
//                        updateCart(response);
                        $(".donation-" + response.id).text("Donation: " + response.percent)
                        ajaxProcessStop();
                    }
                });
            });

            var flag = true;
            $("#dis-sub").click(function(){

                setDiscount();
                flag = true;
            });

            function setDiscount() {
                var value = $("#discount").val();
                $.get("/shop/add_discount/" + value,
                    function(data){
                        if(data)
                        {
                            if (flag) {
                                flag = false;
                                setDiscount();
                            } else {
                                $("#error").text("");
                                $("#discount-value").text("$"+data.discount);
                                $("#tot").text("$"+data.total);
                                $("#responce").css('color', 'blue').text("Coupon added");
                                setTimeout(function(){
                                    $("#responce").text("");
                                }, 5000)
                            }


                        }
                        else
                        {
                            $("#responce").css('color', 'blue').text("Coupon added");
                            //$("#responce").text("Ivalid discount code!");
                            setTimeout(function(){
                                $("#responce").text("");
                            }, 5000)
                        }
                    },
                    'json'
                )
            }

            $('.qtyDecrement, .qtyIncrement').click(function(event) {
                var productId = $(this).attr('rel').replace('product-', '');
                var direction = ($(this).hasClass('qtyIncrement')) ? '+' : '-';
                if(direction == '+') {
                    if(document.getElementById('product-qty-'+productId).value == $('.qtyIncrement[rel="product-'+productId+'"]').data('available')) {
                        alert('No more items in stock');
                        return false;
                    }
                }
                $.ajax({
                    url: '/shop/setQuantity',
                    type: 'post',
                    data: {
                        productId: productId,
                        direction: direction
                    },
                    dataType: 'json',
                    beforeSend: ajaxProcessStart,
                    complete: ajaxProcessStop,
                    success: function(response) {
                        updateCart(response);
                    }
                });

                event.preventDefault();
            });
           function ajaxProcessStart()
           {
               /*var layout = $('.content_for_layout');
               layout.addClass('cartAjaxProcess');
               $('#cartAjaxProcessIcon').css({
                   left: layout.position().left + layout.width() / 2,
                   top: layout.position().top + layout.height() / 2,
                   display: "block"
               });*/
           }
           function ajaxProcessStop()
           {
               /*$('.content_for_layout').removeClass('cartAjaxProcess');
               $('#cartAjaxProcessIcon').hide();*/
           }

           function updateCart(cartData)
           {

               if (cartData.Products.length == 1 && cartData.Products[0].percentId == cartData.Gift) {
                    var giftFlag = true;
               }

               if(cartData.Products.length == 0 || giftFlag)
               {
                   $(".shopping-cart").fadeOut();
                   $("#cart-block").html($("<h1>Your cart is empty</h1>").fadeIn())
               }
               if (cartData.Products.length == 0) {
                   $("#basket").fadeOut('slow', function(){
                       $("#empty").fadeIn();
                   });

               }
               $("#count").text(cartData.Products.length);
               $('#cartSubTotal').html('$' + cartData.Subtotal);
               $('#ship').html('$' + cartData.Shipping);
               $('#discount-value').html('$' + cartData.Discount);
               $('#tot').html('$' + cartData.Total);
               $('#charity-value').html('$' + cartData.Charity);

               $.each(cartData.Products, function(key, product){
                   var prodId = product.percentId.split(".");
                   var id = product.id.replace('.', '\\.');
                   $('#product-qty-' + id).val(product.qty);

                   $('#product-price-' + id).html('$' + product.price);

                   if (parseFloat(product.total) > 0) {
                       $('#product-total-' + id).html('$' + product.total);
                   } else {
                       $('#product-total-' + id).html('Gift');
                   }




                   if (parseFloat(product.percent) > 0) {

                      $('.donation-' + prodId).text('Donation: $' + product.percent);
                   }
               });
           }
            $('.btn-update-cart').click(function(event) {
                $.ajax({
                    url: '/shop/updateCart',
                    type: 'post',
                    data: {

                    },
                    dataType: 'json',
                    beforeSend: ajaxProcessStart,
                    complete: ajaxProcessStop,
                    success: function(response) {
                        updateCart(response);
                    }
                });

                event.preventDefault();
            });
            $("#checkout").live("click", function(){
                var data = $("#ship").text();
                if(data != "$0.00")
                {
                    location.href='/checkout.html'
                }
                else
                {
                    alert("Pleace enter Postal Code!");
                }
            })
        });
    })(jQuery)
    function validateZIP(field) {
        var valid = "0123456789-";
        var hyphencount = 0;
        if (field.length!=5 && field.length!=10) {
        alert("Please enter your 5 digit or 5 digit+4 zip code.");
        return false;
        }
        for (var i=0; i < field.length; i++) {
        temp = "" + field.substring(i, i+1);
        if (temp == "-") hyphencount++;
        if (valid.indexOf(temp) == "-1") {
        alert("Invalid characters in your zip code.  Please try again.");
        return false;
        }
        if ((hyphencount > 1) || ((field.length==10) && ""+field.charAt(5)!="-")) {
        alert("The hyphen character should be used with a properly formatted 5 digit+four zip code, like '12345-6789'.   Please try again.");
        return false;
           }
        }
        return true;
        }
    function getShipping()
    {
        var res = validateZIP(jQuery("#ShippingZip").val());
        if(res){

            var layout = jQuery('.content_for_layout');
            layout.addClass('cartAjaxProcess');
            jQuery('#cartAjaxProcessIcon').css({
                left: layout.position().left + layout.width() / 2,
                top: layout.position().top + layout.height() / 2,
                display: "block"
            });


        jQuery.get(
            "/ups/set_zip/" + jQuery("#ShippingZip").val(),
            function(data) {
                jQuery("#ShippingZip").removeAttr("disabled");

                if(data != " ") {
                    $("#sv").val("1");
                    jQuery("#radio-button").html(data);
                    jQuery("input[name=\"data\[Ups\]\[shipping\]\"]").click(function() {
                        jQuery("#ship").text("$" + jQuery(this).val());
                        setShipping(jQuery(this).val(), jQuery(this).attr("id").substr(3, 1));
                    });

                    jQuery("#ship").text("$" + jQuery("input[name=\"data\[Ups\]\[shipping\]\"]:checked").val());
                    setShipping(
                        jQuery("input[name=\"data\[Ups\]\[shipping\]\"]:checked").val(),
                        jQuery("input[name=\"data\[Ups\]\[shipping\]\"]:checked").attr("id").substr(3, 1)
                    );
                }
                jQuery('.content_for_layout').removeClass('cartAjaxProcess');
                jQuery('#cartAjaxProcessIcon').hide();
            }
        );
        jQuery("#ShippingZip").val("");
        jQuery("#ShippingZip").attr("disabled", true);
        }
        else
        {
            return false;
        }
    }

    function setShipping(value, type)
    {
        var value   = value ? value : 0;
        var type    = type ? type : 0;

        jQuery.get("/shop/set_shipping/" + value + "/" + type,
            function(data) {
                jQuery("#tot").text(data);
            }
        );
    }
