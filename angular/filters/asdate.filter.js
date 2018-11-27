export function asDateFilter () {
    return function (input) {
         return moment(input).format();
     }
 }
