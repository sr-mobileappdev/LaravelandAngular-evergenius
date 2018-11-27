<!DOCTYPE html>
<html lang="en">

<head>
    <title>EverGenius</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="../../css/style-review.css">
    <link rel="stylesheet" href="../../css/owl.carousel.min.css">
    <link rel="stylesheet" href="../../css/owl.theme.default.min.css">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>

    <script src="../../js/owl.carousel.min.js"></script>
    <style type="text/css">
        .owl-carousel .owl-item img {
            display: inline;
            width: auto;
        }
        @if(isset($form_style['bodyBackground']))
        .body-bg{ background: #{{$form_style['bodyBackground']}} }  
        @endif
        @if(isset($form_style['bodyBackground']))
        .body-bg{ color: #{{$form_style['textColor']}} }  
        @endif
        @if(isset($form_style['bodyTextSize']))
        .body-bg{ font-size: {{$form_style['bodyTextSize']}}px; }
        .preview-box blockquote { font-size: {{$form_style['bodyTextSize']}}px; }
        @endif
        @if(isset($form_style['verifiedTextBackground']))
        .blockquote-verified .btn-success { background-color: #{{$form_style['verifiedTextBackground']}} }  
        @endif
        @if(isset($form_style['verifiedTextColor']))
        .blockquote-verified .btn-success { color: #{{$form_style['verifiedTextColor']}} }  
        @endif
        

    </style>

</head>
<body>
    <div class="display-review body-bg">
        <div class="display-review-box">
			<div class="row">
				<div class="col-xs-6  col-sm-3">
					<div class="number-rating {{$rating_scale['class']}}">
						<div class="numbers">
                            @if($total_reviews!=0)
                            {{$avg_rating}}
                            @else
                            0
                            @endif
							/5
						</div>
						<div class="rating-status">
							{{$rating_scale['title']}}
						</div>
						<div class="out-of">
							Out of 5
						</div>
					</div>
				</div>
				
				<div class="col-xs-6 col-sm-4">
					<div class="rating-progress-bars">
						<ul>

                             @if($total_reviews!=0)
							@foreach($rating_stars as $rate)
                            
							<li><span>{{$rate['rating']}}</span> <img src="../../img/icon-star.png">
								<div class="progress">
									<div class="progress-bar" role="progressbar" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100" style="width:{{round(($rate['total_reviews']/$total_reviews)*100,2)}}%">
										<span class="sr-only">{{round(($rate['total_reviews']/$total_reviews)*100,2)}}% Complete</span>
									</div>
								</div> {{$rate['total_reviews']}}
							</li>
                           
							@endforeach
                             @endif


						</ul>
					</div>
				</div>
				
				<div class="col-sm-5">
					<div class="hundred-per">
						<div class="text clearfix">
							<div class="per-value">
                            @if($total_reviews!=0)   
                            {{number_format(($rating_more_4/$total_reviews)*100,2)}}
                            @else
                            0
                            @endif
                        %</div>
							<div class="value-text">of customers who buy this product, give it a 4 or 5 star rating.</div>
						</div>
						<!--div class="add-review">
							<a href="#" class="btn btn-primary">Add a review</a>
						</div-->
					</div>
				</div>
			</div>
        </div>
        <div class="owl-carousel owl-theme">
             @if($total_reviews!=0)
            @foreach($review_listing as $rate_item)
            
            <div class="item">
                <div class="preview-box">
                    <div class="blockquote-header clearfix">
                        <div class="blockquote-author">
                             @if($rate_item['img_url']!=null)
                               <img src="{{$rate_item['img_url']}}"> 
                               @else
                               <img src="../../img/user-100x100.jpg"> 
                               @endif
                            <div>{{ucwords($rate_item['first_name'])}} {{ucwords($rate_item['last_name'])}} <br> <small>Las Vegas</small></div>
                        </div>

                        <div class="blockquote-verified">
                            <div class="star-counting star-{{$rate_item['rating']}}"></div>
                           
                            <span class="btn btn-success" style="cursor: default;"><i class="fa fa-check-circle"></i> {{$form_style['verifiedText']}}</span>
                            <br> 
                            @if(isset($form_style['showDate']) && $form_style['showDate']=='true')
                            <small>
                            {{date('M d,Y',strtotime($rate_item['published_time']))}}</small>
                            @endif
                        </div>
                    </div>
                    <blockquote>
                        {{ucwords($rate_item['user_review'])}}
                    </blockquote>
                </div>
            </div>
            
            @endforeach
            @endif
        </div>
        @if(isset($form_style['honestDoctorLogo']) && $form_style['honestDoctorLogo']=='true')
        <div class="powered-by">
            Powered By: <img src="../../img/img-honest-doctor.png">
        </div>
        @endif
    </div>
    <script>
        $('.owl-carousel')
            .owlCarousel({
                loop: true,
                margin: 20,
                autoplay: true,
                autoplayTimeout: 5000,
                smartSpeed: 1000,
                dots: false,
                nav: false,
                responsive: {
                    0: {
                        items: 1
                    },
                    600: {
                        items: 1
                    },
                    1000: {
                        items: 1
                    }
                }
            });
    </script>
</body>
</html>