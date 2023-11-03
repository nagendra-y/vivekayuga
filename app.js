var cmGlobal = angular.module('crowdmeta', []);


cmGlobal.controller('cmController', ['$scope', '$window', '$compile', '$timeout', '$anchorScroll', 
		function($scope, $window, $compile, $timeout, $anchorScroll) {
				$scope.pledge_amount = 5000;
				$scope.customerProfile = {};
				
				$scope.user_profile = {};
				$scope.user_profile.image_src = "images/pfp-supporter.jpg";
				
				$scope.campaign = {
						"title": "Vivekayuga Foundation",
                    "description": "We are in to value education since 2002. Creating awareness about the great Indian human values of life building among our students, youth... etc through Seminars, Personality Development Workshops in different parts of Karnataka.",
						"raised": 0, 
						"target": 3000000, 
						"days": 42,
						"image": 'images/g8.jpeg',
						"thumbnails" : ['images/g8.jpeg','images/g7.jpeg', 'images/g1.jpeg', 'images/g2.jpeg'],
						"supporters" : [],
						"rewards": []
				};


            $scope.campaign.gallery = [
                'images/g1.jpeg',
                'images/g2.jpeg',
                'images/g3.jpeg',
                'images/g4.jpeg',
                'images/g5.jpeg',
                'images/g6.jpeg',
                'images/g7.jpeg',
                'images/g8.jpeg'
            ];


				$scope.campaign.rewards = [
						{ 	
								"type": 0,
								"id": 9885,
								"amount": 5000,
								"amount_usd": 70,
								"title": "Reward Tier-0",
								"description": "One copy of the coffee-table book(worth ₹7500) delivered to you at an incredible discounted price!",
								"image": "images/main-0.jpg",
								"supporters" : [],
						},
						{ 	
								"type": 0,
                "id": 9886,
								"amount": 9999,
								"amount_usd": 140,
								"title": "Reward Tier-1",
								"description": "Pledge ₹9999 to get two copies of the coffee-table book + a watermark free downloadable image of your choice from the Gallery",
								"image": "images/main-1.jpg",
								"supporters" : [],

						},
						{ 	
                "type": 0,
                "id": 9886,
								"amount": 15000,
								"amount_usd": 210,
								"title": "Reward Tier-2",
								"description": "Pledge ₹15000 to get three copies of the coffee-table book + a watermark free downloadable image of your choice from the Gallery",
								"image": "images/main-2.jpg",
								"supporters" : [],

						},
						{ 	
                "type": 0,
                "id": 9886,
								"amount": 25000,
								"amount_usd": 350,
								"title": "Reward Tier-3",
								"description": "Pledge ₹25000 to get three copies of the coffee-table book + A Framed 12 x 15 print chosen by you from the Gallery",
								"image": "images/main-3.jpg",
								"supporters" : [],

						}
				];
				
				//TODO: Merge rewards into products
				$scope.products = [
						
						{ 	
								"type": 1,
								"id": 7885,
								"amount": 4500,
								"original_amount": 5500,
								"amount_usd": 60,
								"description": "A comprehensive study of most of the historically significant forts of Karnataka",
								"title":"Karunaada Kotegala Suvarna Nota",
								"image": "images/cover-karunad-kote.jpg",
								"thumbnails": ["images/cover-karunad-kote.jpg", "images/back-karunad-kote.jpg"]
						}
				];
				
				$scope.selected_product = null;
				$scope.preview_product = null;

				function readInput(ele_id, defaultVal){
						if(!ele_id)
								return defaultVal;

						let input = "";
						input = $('#'+ele_id).val();
						console.log(ele_id, input);
						if(!input)
								return defaultVal;

						return input;

				}
				$scope.getOrderJson = function(){
						let name = "Test";
						let email ="me@example.com";
						let phone = "3855828639";
						let address = "Abc, Xyz St.";
						let state = "Karnataka";
						let district = "Bangalore Urban";
						let pincode = "560001";
						let refid = "26abcdef";
						let mode = 0;
						let amount = $scope.pledge_amount;
						let items = [];
						let photo_type  = "";

						name = readInput('name', name);
						email = readInput('email', email);
						phone = readInput('phone', phone);
						address = readInput('address', address);

						$scope.user_profile.name = name;
						$scope.user_profile.email = email;
						$scope.user_profile.phone = phone;
						$scope.user_profile.address = address;
						$scope.user_profile.image = $scope.user_profile.image;


						if($scope.user_profile.image){
								photo_type = $scope.user_profile.image.type;
								photo_type = photo_type.split("/");
								photo_type = photo_type[1];
						}

						// state =  eleState.value;
						// district =  eleDistrict.value;
						// pincode =  eleZip.value;

						// var urlRefId = getURLParameter("refid");
						// if(urlRefId)
						//      refid = urlRefId;

						// mode =  parseInt(elePayMode.value);
						// items = $scope.gCartItems;
						// amount =  $scope.getCartTotal();

						let order =     {
								"name": name,
								"email": email,
								"phone": phone,
								"photo_type": photo_type,
								"address": address,
								"state": state,
								"district": district,
								"pincode": pincode,
								"refid": refid,
								"mode": mode,
								"amount": amount,
								"photo_type": photo_type
						};

						console.log(order);

						return order;
				}


				$scope.checkoutToGateway = function(){

						const data = $scope.getOrderJson();
						
						fetch('backend/api/checkout/pay.php', {
								// mode: 'cors',
								method: 'POST', // or 'PUT'
								headers: {
										'Content-Type': 'application/json',
								},
								body: JSON.stringify(data),
						})
								.then(response => response.json())
								.then(data => {
										console.log('Success:', data);

										$('#exampleModal').modal('hide');
										$scope.processRPay(data.order);

										$scope.user_profile.cid = data.cid;
										if(data.uploadUrl && data.photo){
												console.log(data.uploadUrl);
												$scope.uploadImage(data.uploadUrl, $scope.user_profile.image);		
										}


								})
								.catch((error) => {
										console.error('Error:', error);
								});

				}

				$scope.uploadImage = function(presignedUrl, file){
						let formData = new FormData();
						formData.append("image", file);
						fetch(presignedUrl, {method: "PUT", body: file})
								.then(response => {
										console.log('Upload Success:', response);
										$scope.fetchData();
								})
								.catch((error) => {
										console.error('Error:', error);
								});
				}

				//Send payment creds to server and confirm that the payment is complete
				$scope.verifyPayment = function(rp){
						let data = {
								"razorpay_payment_id": rp.razorpay_payment_id,
								"razorpay_order_id": rp.razorpay_order_id,
								"razorpay_signature": rp.razorpay_signature
						}

						fetch('backend/api/checkout/verify.php', {
								// mode: 'cors',
								method: 'POST', // or 'PUT'
								headers: {
										'Content-Type': 'application/json',
								},
								body: JSON.stringify(data),
						})
								.then(response => response.json())
								.then(data => {
										console.log('Success:', data);
										$scope.sendReceipt({'id': $scope.user_profile.cid});

										$('#certificateModal').modal('show');
								})
								.catch((error) => {
										console.error('Error:', error);
										$('#certificateModal').modal('show');
								});

				}

				$scope.sendReceipt = function(order){
						let data = {'oid': order.id, 'description': 'Reward Description'};	
						fetch('backend/api/email/sendreceipt.php', {
								// mode: 'cors',
								method: 'POST', // or 'PUT'
								headers: {
										'Content-Type': 'application/json',
								},
								body: JSON.stringify(data),
						})
								.then(response => response.json())
								.then(data => {
										console.log('Success:', data);
								})
								.catch((error) => {
										console.error('Error:', error);
										$('#certificateModal').modal('show');
								});

				}


				$scope.processRPay = function(order){

						var options = {
								"key": order.key, // Enter the Key ID generated from the Dashboard. 
								"amount": order.amount, // Amount is in currency subunits. Default currency is INR. Hence, 50000 refers to 50000 paise
								"currency": order.currency,
								"name": "Crowdmeta",
								"description": "Test Transaction",
								"order_id": order.id, 
								"handler": function (response){
										$scope.verifyPayment(response);
								},
								"prefill": {
										"name": order.customer_name,
										"email": order.customer_email,
										"contact": order.customer_phone
								},
								"notes": {
										"address": "Crowdmeta Office"
								},
								"theme": {
										"color": "#3399cc"
								}
						};
						var rzp1 = new Razorpay(options);
						rzp1.on('payment.failed', function (response){
								console.log(response.error.code);
								console.log(response.error.description);
								console.log(response.error.source);
								console.log(response.error.step);
								console.log(response.error.reason);
								console.log(response.error.metadata.order_id);
								console.log(response.error.metadata.payment_id);
						});

						console.log("Redirecting to razorpay..", rzp1);
						rzp1.open();
				}

				$scope.getRewardFromAmount = function(amount){
				
						for(var i=0; i< $scope.campaign.rewards.length; i++){
								var reward = $scope.campaign.rewards[i];
								if(reward.amount == amount)
										return reward;
						}
						
						return null;
				}
				
				$scope.updateStats = function(){
						//Reset reward stats
						for(var i=0; i< $scope.campaign.rewards.length; i++){
								var reward = $scope.campaign.rewards[i];
								reward.supporters = [];
						}

						var amount = 0;
						for (var i = $scope.campaign.supporters.length - 1; i >= 0; i--) {
							var supporter = $scope.campaign.supporters[i];
						  var samount = parseInt(supporter.amount);
							amount+= samount;
						  var matching_reward = $scope.getRewardFromAmount(samount);
						  matching_reward.supporters.push(supporter);
						}

						$scope.campaign.raised = amount;
						$scope.$applyAsync();
				}

				$scope.fetchData = function(){
						fetch('backend/api/supporters/list.php', {
								method: 'GET', // or 'PUT'
						})
								.then(response => response.json())
								.then(data => {
										console.log('Success:', data);
										if(data["supporters"])
												$scope.campaign.supporters = data["supporters"];
										$scope.updateStats()
										$scope.$applyAsync();
								})
								.catch((error) => {
										console.error('Error:', error);
								});
				}
				$scope.refreshData = function(){
						//$scope.fetchData();
						//setTimeout($scope.refreshData, 30000); //Every 30 seconds
				}

				$scope.enterCheckout = function(product){
						$scope.selected_product = product; //the product can be a reward or a shop item,etc.
						console.log("Checkout..");
						$('#exampleModal').modal('show'); //The product info will be shown on the checkout page
				}
			
				$scope.getPhoto = function(s){
						if(s.photo)
								return "https://storage.googleapis.com/crowdmeta-users/"+ s.photo;

						return "images/pfp-supporter.jpg";
						
				}	

				$scope.getDate = function(s){
						var d = s.created;
						d = d.split(" ");
						d = d[0];
						d = d.split("-");
						var year = d[0];
						var month = parseInt(d[1]);
						var day = d[2]; 

						var months = [ "January", "February", "March", "April", "May", "June",
								"July", "August", "September", "October", "November", "December" ];

						var monthName = months[month];
						return monthName + " "+ day + ", "+ year; 
				}

				$scope.getAmount = function(s){
						var amnt = s.amount;
						return $scope.getLocalAmount(amnt, "inr");
				}

				$scope.getLocalAmount = function(amnt, currency){
						if("inr" == currency){
								amnt = parseInt(amnt);
								amnt = amnt.toLocaleString('en-IN');
								return "₹" + amnt; 
						}

						return "";
				}

				$scope.init = function(){
						$scope.refreshData();
				}

				$scope.readURL = function(input) {
						if (input.files && input.files[0]) {
								var file = input.files[0];
								var reader = new FileReader();

								reader.onload = function (e) {
										$('#wizardPicturePreview').attr('src', e.target.result).fadeIn('slow');
										$scope.user_profile.image_src = e.target.result;

								}
								reader.readAsDataURL(file);
								$scope.user_profile.image = file;
								$scope.user_profile.image = file;

						}

						$scope.$applyAsync();
				}

				$scope.setUserImage = function() {
					if(!$scope.user_profile.image_src)
						return;

					$('#cert-user-image').attr('src', $scope.user_profile.image_src).fadeIn('slow');						
				}

				$scope.showPreview = function(product) {
						$scope.preview_product = product;
						$scope.setFocusThumbnail(product.thumbnails[0]); 
						//Focus first image in thumbnails
						$scope.$applyAsync();
						$('#previewProductModal').modal('show');
				} 

				$scope.setFocusThumbnail = function(tn){
						$scope.preview_product.focusTn = tn;
						$scope.$applyAsync();

				}

				$scope.getSupporterText = function(reward){
					var supporters = reward.supporters;
					if(!(supporters && supporters.length)){
						console.log("No supporters");
						return "";
					}

					var topSupporter = supporters[0];
					var text = topSupporter.name;

					if(supporters.length > 1){
						var remaining = supporters.length - 1;
						text += " and " + remaining;
						
						if(remaining > 1)
							text += " others support this";
						else
							text += " other supports this";
					}
					else{
						text += " supports this";
					}

					return text;

				}
		}]);

$(document).ready(function() {

    const form = document.getElementById('contact_form');
    const booking_form = document.getElementById('booking_form');

    const greetingInput = document.querySelector('input[name="greeting"]');

    const nameInput = document.querySelector('input[name="name"]');
    const emailInput = document.querySelector('input[name="email"]');
    const phoneInput = document.querySelector('input[name="phone"]');
    const messageInput = document.querySelector('textarea[name="message"]');
    const admin_email = 'vivekayugaprakashana@gmail.com';
    const admin_phone = '917019882153';

    function continueWithEmail() {
        const from = encodeURIComponent(emailInput.value);
        const name = encodeURIComponent(nameInput.value);
        let subject = `Query from ${name}`;
        let message = "Hello, I have a query about Vivekayuga.\n\n";
        if (greetingInput) {
            message = greetingInput.value + "\n\n";
            subject = `Query from ${name}`;
        }

        let body = message;

        body += `Message: ${messageInput.value.replace(/(\r\n|\n|\r)/gm, "")}\n`;
        body += `Email: ${emailInput.value}\n`;
        const phoneValue = phoneInput.value.replace(/\D/g, '');
        const phoneCountryCode = iti.getSelectedCountryData().dialCode;
        body += `Phone: +${phoneCountryCode} ${phoneValue}\n`;
        body += `\nThank you,\n${nameInput.value}`; // add "Thank you, {Sender name}" message at the end

        let mailtoLink = `https://mail.google.com/mail/?view=cm&fs=1&to=${admin_email}&su=${subject}&body=${encodeURIComponent(body)}&service=mail`;
        if (navigator.userAgent.match(/(Android|iPhone|iPod|iPad)/i)) {
            mailtoLink = `mailto:${admin_email}?subject=${subject}&body=${encodeURIComponent(body)}`;
        }

        if (navigator.userAgent.match(/(Android|iPhone|iPod|iPad)/i)) {
            window.location.href = mailtoLink;
        } else {
            window.open(mailtoLink, '_blank');
        }
    }

    function continueWithWhatsApp() {
        const name = encodeURIComponent(nameInput.value);
        let messageIntro = "Hello, I have a query about Vivekayuga.\n\n";

        if (greetingInput) {
            messageIntro = greetingInput.value + "\n\n";
        }

        let message = messageIntro;
        message += `Message: ${messageInput.value}\n`;
        const phoneValue = phoneInput.value.replace(/\D/g, '');
        const phoneCountryCode = iti.getSelectedCountryData().dialCode;
        message += `Phone: +${phoneCountryCode} ${phoneValue}\n`;
        message += `Email: ${emailInput.value}\n`;
        message += `\nThank you,\n${nameInput.value}`; // add "Thank you, {Sender name}" message at the end

        let whatsappLink = `https://web.whatsapp.com/send?phone=${admin_phone}&text=${encodeURIComponent(message)}`;
        if (navigator.userAgent.match(/(Android|iPhone|iPod|iPad)/i)) {
            whatsappLink = `https://wa.me/${admin_phone}?text=${encodeURIComponent(message)}`;
        }
        window.open(whatsappLink, '_blank');
    }

    window.continueWithWhatsApp = continueWithWhatsApp;
    window.continueWithEmail = continueWithEmail;

    console.log(form); 
    if (form) {
        form.addEventListener('submit', (event) => {
            console.log("submit");
            event.preventDefault();
            if (validateInputs()) {
                showModal();
            }
        });
    }

    if (booking_form) {
        booking_form.addEventListener('submit', (event) => {
            event.preventDefault();
            if (validateInputs()) {
                showModal();
            }
        });
    }

    function validateInputs() {
        let isValid = true;
        if (nameInput.value.trim() === '') {
            nameInput.classList.add('is-invalid');
            isValid = false;
        } else {
            nameInput.classList.remove('is-invalid');
        }
        if (emailInput.value.trim() === '' || !isValidEmail(emailInput.value)) {
            emailInput.classList.add('is-invalid');
            isValid = false;
        } else {
            emailInput.classList.remove('is-invalid');
        }
        if (messageInput.value.trim() === '') {
            messageInput.classList.add('is-invalid');
            isValid = false;
        } else {
            messageInput.classList.remove('is-invalid');
        }
        return isValid;
    }

    function isValidEmail(email) {
        // simple email validation using regex
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function showModal() {
        console.log("showModal");
        const modal = document.createElement('div');
        modal.classList.add('modal', 'fade');
        modal.setAttribute('tabindex', '-1');
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-hidden', 'true');

        modal.innerHTML = `
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Choose an option</h5>
            <button type="button" onclick="$('.modal').modal('hide')" class="close" data-dismiss="modal" aria-label="Cancel">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p>How would you like to continue?</p>
          </div>
          <div class="modal-footer justify-content-center">
            <button type="button" class="btn btn-primary" data-dismiss="modal" onclick="window.continueWithEmail()">
              <i class="fa fa-envelope"></i> Continue with Email
            </button>
            <button type="button" class="btn btn-success" data-dismiss="modal" onclick="window.continueWithWhatsApp()">
              <i class="fa fa-whatsapp"></i> Continue with WhatsApp
            </button>
          </div>
        </div>
      </div>
    `;

        document.body.appendChild(modal);
        $(modal).modal('show');
    }

    $(document).ready(function () {
        const input = document.querySelector("#phone");
        const errorMsg = document.querySelector("#error-msg");
        const validMsg = document.querySelector("#valid-msg");

        // here, the index maps to the error code returned from getValidationError - see readme
        const errorMap = ["Invalid number", "Invalid country code", "Too short", "Too long", "Invalid number"];

        // initialise plugin with options
        window.iti = window.intlTelInput(input, {
            initialCountry: "in",
            utilsScript: "intltelinput-utils.js"
        });

        const reset = () => {
            input.classList.remove("error");
            errorMsg.innerHTML = "";
            errorMsg.classList.add("hide");
            validMsg.classList.add("hide");
        };

        // on blur: validate
        input.addEventListener('blur', () => {
            reset();
            if (input.value.trim()) {
                if (iti.isValidNumber()) {
                    validMsg.classList.remove("hide");
                } else {
                    input.classList.add("error");
                    const errorCode = iti.getValidationError();
                    errorMsg.innerHTML = errorMap[errorCode];
                    errorMsg.classList.remove("hide");
                }
            }
        });

        // on keyup / change flag: reset
        input.addEventListener('change', reset);
        input.addEventListener('keyup', reset);

        // hide validMsg until input has value
        input.addEventListener('input', () => {
            if (input.value.trim()) {
                validMsg.classList.add("hide");
            }
        });
    });



});

