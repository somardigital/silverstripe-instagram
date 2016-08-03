<% if $media %>
<div class="instagram-media">
    <% loop $media %>
    <div class="instagram-media__item--$type">
        <a href="$link" title="View on Instagram" target="_blank">
            <img class="instagram-media__image" src="$images.low_resolution.url" />
        </a>

        <% if $caption %>
        <p class="instagram-media__caption">$caption.text</p>
        <% end_if %>

        <% if $tags %>
        <div class="instagram-media__tags">
            <% loop $tags %>
            <a
                href="https://www.instagram.com/explore/tags/$name"
                title="View on Instagram"
                target="_blank">
                #$name
            </a>
            <% end_loop %>
        </div>
        <% end_if %>
    </div>
    <% end_loop %>

    <% if $loadMoreLink %>
    <div class="instagram-media__nav">
        <a class="instagram-media__nav-item" href="$loadMoreLink" title="View more">Load more</a>
    </div>
    <% end_if %>
</div>
<% end_if %>
