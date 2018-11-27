import { DateMillisFilter } from './filters/date_millis.filter'
import { CapitalizeFilter } from './filters/capitalize.filter'
import { HumanReadableFilter } from './filters/human_readable.filter'
import { TruncatCharactersFilter } from './filters/truncate_characters.filter'
import { TruncateWordsFilter } from './filters/truncate_words.filter'
import { TrustHtmlFilter } from './filters/trust_html.filter'
import { UcFirstFilter } from './filters/ucfirst.filter'
import { asDateFilter } from './filters/asdate.filter'
import { asTimeDiffFilter } from './filters/astimediff.filter'
import { strReplaceFilter } from './filters/str_replace.filter'
import { GetWebpageTitle } from './filters/get_webpage_title.filter'
import { RmunderscoreFilter } from './filters/rmunderscore.filter'
import { secToTime } from './filters/sectotime.filter'

angular.module('app.filters')
    .filter('datemillis', DateMillisFilter)
    .filter('capitalize', CapitalizeFilter)
    .filter('humanreadable', HumanReadableFilter)
    .filter('truncateCharacters', TruncatCharactersFilter)
    .filter('truncateWords', TruncateWordsFilter)
    .filter('trustHtml', TrustHtmlFilter)
    .filter('ucfirst', UcFirstFilter)
    .filter('asDate', asDateFilter)
    .filter('astimeDiff', asTimeDiffFilter)
    .filter('strReplace', strReplaceFilter)
    .filter('secToTime', secToTime)
    .filter('webpageTitle', GetWebpageTitle)
    .filter('rmunderscore', RmunderscoreFilter)
    .filter('tel', function () {
        return function (tel) {
            if (!tel) { return ''; }

            var value = tel.toString().trim().replace(/^\+/, '');

            if (value.match(/[^0-9]/)) {
                return tel;
            }

            var country, city, number;

            switch (value.length) {
                case 1:
                case 2:
                case 3:
                    city = value;
                    break;

                default:
                    city = value.slice(0, 3);
                    number = value.slice(3);
            }

            if (number) {
                if (number.length > 3) {
                    number = number.slice(0, 3) + '-' + number.slice(3, 7);
                }
                else {
                    number = number;
                }

                return ("(" + city + ") " + number).trim();
            }
            else {
                return "(" + city;
            }

        };
    })
    .filter('groupBy', function () {
        return _.memoize(function (items, field) {
            return _.groupBy(items, field);
        }
        );
    })