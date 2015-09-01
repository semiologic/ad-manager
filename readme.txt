=== Ad Manager ===
Contributors: Denis-de-Bernardy, Mike_Koepke
Donate link: https://www.semiologic.com/donate/
Tags: semiologic, ads, adsense
Requires at least: 2.8
Tested up to: 4.3
Stable tag: trunk

A widget-driven ad manager, suitable for AdSense, YPN, and many more.


== Description ==

The Ad Manager plugin for WordPress allows you to manage advertisement space on your site.

It is widget-driven, and plays best with widget-driven themes such as the Semiologic theme, especially when combined with the Inline Widgets plugin.

The Ad Manager plugin tracks which type of ads are displayed on your site, so as to respect Google's and Yahoo's AUP at all times.

= Placing an Ad Unit in a panel/in a sidebar =

It's short and simple:

1. Browse Appearance / Widgets
2. Open the panel of your choice (or sidebar, if not using the Semiologic theme)
3. Place an "Ad Widget" in that panel/sidebar
4. Configure that ad widget as needed

To configure your ad widget, paste whichever javascript (or arbitrary HTML code) you'd like it to output.

= Click protection =

Ad Widgets will not display their output to your site's editors and administrators. They do display to authors, allowing the use of the plugin on sites where guest contributors can publish posts.

Consistent with common Acceptable Use Policies (AUP), no ads are output on 404 errors either.

= Ad Unit contexts =

You can optionally configure ad widgets to only display when certain conditions are met:

- The visitor comes from a search engine
- The post (not page) is more that 2 weeks old
- The visitor is not a regular reader (three visits in the past two weeks)
- A php condition (WordPress conditional tags) is met

These conditions are cumulative: if you check several conditions, the add won't display unless all are met.

Note that some of these contexts (search engines and regular visitors) are ignored when the plugin detects you're using a static cache -- i.e., Semiologic Cache, WP-Cache, and all of the plugins based on the latter.

= Automatically floating ad units in your content =

To automatically float an ad unit to the top left of your posts when using the Semiologic theme:

1. Open the Each Entry panel, under Appearance / Widgets
2. Place your ad unit immediately above the "Entry: Content" widget
3. Configure the widget to float to the left

Ad units in this location give the best click-through rates with wide ads according to Google. Other good places to place ad units include:

- To the top/middle right of your site in a sidebar. This would be for a tower ad. Users commonly swipe their mouse to the top right corner of their screen, and eyeballs generally look for it in that area once they're done reading.
- Immediately after the "Entry: Content" widget. A large ad would apply here, and it would be aiming for revenue-generating bounces. This is an excellent location if your site's content is of good quality.
- In your header, where your navigation menu would normally be placed. Whether you leave any actual navigation menu is up to you -- most users who place an ad here don't expect users to even go as far as read their content.

= Embedding ad units in your entries =

In case you desire to place ads directly in an entry's contents, combine this plugin with Inline Widgets:

1. Open the Inline Widgets panel, under Appearance / Widgets
2. Place and configure an Ad Widget
3. Edit a post or a page; note the "Widgets" drop down menu
4. Select your newly configured ad unit in the "Widgets" drop down menu to insert it where your mouse cursor is at

= Boosting your click-through rates =

There are a few known tricks to get higher click-through rates. They really go down to common sense.

Good content works, as long as it's not "too good." (Think "useful but incomplete.") If your reader has learned everything he needs to learn upon reading your content, he has little incentive to "learn more" by clicking -- whether it is on your site or to somewhere else.

At the other end of the spectrum, bad content works too. (Think "very obviously useless.") But then, make sure you offer opportunities to run away from your site (through ads, of course) early on the page. Also, you'll need to think of your site(s) as disposable: have an ample supply of domains, and a separate AdSense account for your good sites.

Attracting eye contact works well too. Placing one or more eye catching images immediately above a text ad, for instance, does a great job at attracting eye balls.

= Google Analytics integration =

Combining this plugin with the Google Analytics (GA) plugin adds an interesting bonus. Specifically, be sure to tie your GA account to your AdSense account. The GA plugin adds the needed code to integrate your site's stats with your Ad revenue.

"Home-made" ads are also tracked, but differently: any click in an ad unit is tracked as a GA event, provided the click doesn't occur in an iframe tag. (Their provider's AUP generally disallows to change their code, but the AdSense team plans to roll out a very similar feature shortly.)

= Split testing / Ad rotating =

Just to answer the FAQ, since it creeps up every now and then... It's not implemented, for two reasons.

First and foremost, it doesn't scale. There are basically two ways to implement an ad rotator. One is to use a service that rotates ads for you. The other is to rotate ads yourself -- and doing so will generally require server-side scripting. Rotating ads using server-side scripting rules out the use of any cache plugin.

Arguably, you might not need a caching plugin. But if you don't, you probably aren't getting enough traffic to do split testing either. In other words, you're seeking to rotate for the mere sake of rotating. And this brings us to the second reason: shouldn't you be focused on finding the setup that maximizes your revenue instead?

To split test ad setups or ad providers, test them one at a time, one after the other. Each, until you've a statistically meaningful sample.

By the way, by meaningful sample, I'm not meaning 1,000 visitors or 100 clicks. I'm meaning $20, $50, more... you're aiming for revenue, not clicks. A few high paying ads might get low click-through rates, and early or late spikes of clicks. Be sure to get that sampling right, else you might miss it.


= Help Me! =

The [Semiologic Support Page](https://www.semiologic.com/support/) is the best place to report issues.


== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress


== Change Log ==

= 2.5 =

- WP 4.0 compat

= 2.4.1 =

- Fix localization

= 2.4 =

- Code refactoring
- WP 3.9 compat

= 2.3 =

- Fix issue where ads wouldn't be displayed if the condition was not actual php code.
- Added bing, baidu and yandex to search engine referrers.
- WP 3.8 compat

= 2.2 =

- WP 3.6 compat
- PHP 5.4 compat

= 2.1.2 =

- WP 3.5 compat

= 2.1.1 =

- Improve cache support
- Fix php condition handler

= 2.1. =

- JS-based click protection, to allow for proper preview of units

= 2.0 =

- Complete rewrite
- WP_Widget class
- Localization
- Code enhancements and optimizations
