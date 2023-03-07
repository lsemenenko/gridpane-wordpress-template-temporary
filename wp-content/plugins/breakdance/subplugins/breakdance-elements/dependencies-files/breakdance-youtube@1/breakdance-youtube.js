// Wrapper for interacting with YouTube Embedded Videos
(function() {
  function BreakdanceYoutube() {
    return {
      instances: {},
      interval: null,
      createInstance(element, id, config) {
        const player = new YT.Player(element, {
          playerVars: {
            autoplay: 1,
            controls: 0,
            loop: false,
            modestbranding: 1,
            rel: 0,
            origin: window.location.origin
          },
          events: {
            onReady: () => {
              const startTime = config.start_time ?? 0;
              player.seekTo(startTime);
              let endTime =
                config.end_time ?? this.instances[id].getDuration() - 1;
              // YouTube doesn't provide a 'timeupdate' event, so we have to do this manually
              this.interval = setInterval(() => {
                const player = this.instances[id];
                if (player.playerInfo.currentTime >= endTime) {
                  if (config.loop === false) {
                    player.pauseVideo();
                  } else {
                    player.seekTo(startTime);
                  }
                }
              }, 1000);
              if (config.pause_when_out_of_view === true) {
                this.pauseVideoWhenNotInViewport(element, id);
              }
            }
          }
        });
        this.instances[id] = player;
      },
      updateInstance(element, id, config) {
        if (this.instances[id]) {
          this.destroyInstance(id, element);
        }
        const newElement = document.querySelector(
          "#youtubeEmbed" + id + " iframe"
        );
        this.createInstance(newElement, id, config);
      },
      destroyInstance(id, element) {
        const player = this.instances[id];
        if (!player) {
          return;
        }
        const iframeHtml = element.outerHTML;
        clearInterval(this.interval);
        player.destroy();
        this.instances[id] = null;
        const wrapper = document.querySelector("#youtubeEmbed" + id);
        wrapper.innerHTML = iframeHtml;
      },
      pauseVideoWhenNotInViewport(element, id) {
        let isPaused = false;
        let observer = new IntersectionObserver(
          (entries, observer) => {
            entries.forEach(entry => {
              const player = this.instances[id];
              if (
                entry.intersectionRatio !== 1 &&
                player.getPlayerState() === YT.PlayerState.PLAYING
              ) {
                player.pauseVideo();
                isPaused = true;
              } else if (isPaused) {
                player.playVideo();
                isPaused = false;
              }
            });
          },
          { threshold: 0.05 }
        );
        observer.observe(element);
      }
    };
  }

  window.breakdanceYoutube = BreakdanceYoutube();
})();
