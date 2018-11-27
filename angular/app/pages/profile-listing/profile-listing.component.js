class ProfileListingController {
    constructor(API, $state, AclService) {
        'ngInject'

        this.API = API
        this.$state = $state
        this.alerts = []
        this.publishers = [];
        let profile_listing = this.API.service('profilelisting')
        profile_listing.one("").get()
            .then((response) => {
                let dataSet = response.plain()
                this.profile_listing_data = dataSet.data;
            });
        let profile_publishers = API.service('publisher-list', API.all('profilelisting'))
        profile_publishers.one("").get()
            .then((response) => {
                let dataSet = response.plain()
                let publishers = []
                angular.forEach(dataSet.data.response.publishers, function (value) {
                    publishers[value.id] = value.name.toString();
                })
                this.publishers = publishers;

            });

        this.can = AclService.can
        if (!this.can('manage.profile')) {
            $state.go('app.unauthorizedAccess');
        }


        this.publisherName = function (publisherID) {

            let all_publishers = this.publishers;
            if (all_publishers[publisherID]) {
                return all_publishers[publisherID];
            } else {
                return this.capitalizeFirstLetter(publisherID);
            }
        }

        this.ListingUrlExists = function (urls) {
            if (typeof (urls) != 'undefined' || urls != '') {
                return true;
            }
            return false;
        }
        this.capitalizeFirstLetter = function (string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }

    }


    $onInit() { }
}

export const ProfileListingComponent = {
    templateUrl: './views/app/pages/profile-listing/profile-listing.component.html',
    controller: ProfileListingController,
    controllerAs: 'vm',
    bindings: {}
}
