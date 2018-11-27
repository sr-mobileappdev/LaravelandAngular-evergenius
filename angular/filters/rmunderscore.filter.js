export function RmunderscoreFilter () {
    return function (text) {
      if (isNaN(text)){
        var frags = text.split('_');
        for (i = 0; i < frags.length; i++) {
          frags[i] = frags[i].charAt(0).toUpperCase() + frags[i].slice(1);

          if (frags[i] == 'Mobile') {
            var phnno = 'Phone Number'
            return phnno
          }
        }
        return frags.join(' ');
      }else{
        return ''
      }
         
    };
}
