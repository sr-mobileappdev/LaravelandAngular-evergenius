export function GetWebpageTitle (API, $rootScope) {
   return function (input,type_media) {
   	    var titleUrl = API.service('website-meta', API.all('social'))
        var title_data = '';
        return titleUrl.one().get({
            'url':input
        })
        .then((response) => {
        		if($rootScope.posts==undefined){
        			$rootScope.posts = new Object;
        		}
                let respo = response.plain();
                var all_data = respo.data;
                title_data=all_data;
                $rootScope.posts.push(type_media);
       });

    }
}
