export function asTimeDiffFilter () {
   return function (input) {
        return moment(input).fromNow(true);

    }
}