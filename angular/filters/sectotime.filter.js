export function secToTime () {
    return function (input) {
        let duration = input;
        var minutes = Math.floor(duration / 60); // 7
        var seconds = duration % 60; // 30
        return minutes + ":" + seconds;
    }
  }
  