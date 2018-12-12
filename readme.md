![Lever](resources/hero.svg)

<h1 align="center">Lever Craft CMS 3 Plugin</h1>
<h4 align="center">Get <a href="https://www.lever.co/">Lever</a> job details and post applications directly from Craft.</h4>

---

## Installation & Setup

- `composer require workingconcept/craft-lever`
- `./craft install lever` or install from _Settings_ → _Plugins_ in the control panel
- add your site and API details in the control panel via _Settings_ → _Lever_

## Development

### Templating

#### `craft.lever.jobs([])`

Returns an array of jobs. Supply valid parameters for the [job postings API](https://github.com/lever/postings-api) if you need to tailor the results.

#### `craft.lever.job(id)`

Returns a specific job matching the provided Lever ID, or `false`.

#### `craft.lever.errors`

Returns an array of errors, relevant if you're attempting to submit a job application.

#### Job Properties

- `id`
- `text`
- `categories`
- `description`
- `descriptionPlain`
- `lists`
- `additional`
- `additionalPlain`
- `hostedUrl`
- `applyUrl`
- `createdAt`

### Template Examples

#### List Jobs

```twig
{% set positions = craft.lever.jobs %}

<h2>Work with Us!</h2>

<ul>
{% for position in positions %}
    <li><a href="{{ position.hostedUrl }}" target="_blank">{{ position.text }}</a></li>
{% endfor %}
</ul>

```

### Custom Job Application Form

You can create your own form and validation and submit it with an `action` field set to `lever/apply`. Use any fields named exactly as seen in the [postings API](https://github.com/lever/postings-api), with `jobId`, `name`, and `email` being required.

```twig
<h3>Apply</h3>

{% if craft.lever.errors | length %}
    {% for error in craft.lever.errors %}
        <p class="error" role="alert">{{ error }}</p>
    {% endfor %}
{% endif %}

<form id="job" action="" method="post" accept-charset="UTF-8" enctype="multipart/form-data">
    {{ csrfInput() }}
    <input type="hidden" name="action" value="lever/apply">
    <input type="hidden" name="jobId" value="{{ job.id }}">
    <input type="hidden" name="redirect" value="{{ (craft.app.request.absoluteUrl ~ "?success=y") | hash }}">

    <input type="text" name="name" required>
    <input type="email" name="email" required>
    <textarea name="comments"></textarea>

    <input type="file" name="resume" required>

    <button class="btn">Submit</button>
</form>
```

### Establishing Custom Job Detail Pages

You can display all job details on your site if you'd like, including custom detail pages. One way you might go about that is to create a detail page template like `jobs/_detail.twig` and set up a custom route for it.

Barebones template:

```twig
{% extends "_layout" %}

{% set job = craft.lever.job(id) %}

{% if job is empty %}
    {% exit 404 %}
{% endif %}

{% block content %}

...
```

Custom route in `config/routes.php`:

```php
return [
    'jobs/<id>' => ['template' => 'jobs/_detail'],
];
```

This will take requests like `https://site.foo/jobs/be9f3647-b59a-4272-94a0-8b937520a69f` and send them to your template, where they'll 404 if the ID is invalid.

### Events

##### `EVENT_BEFORE_SEND_APPLICATION`

Triggered after an application is submitted and before it's sent to Lever. Grab the application data from `$event->application` and prevent it from being sent by setting `$event->isSpam` to `true`.

##### `EVENT_AFTER_SEND_APPLICATION`

Triggered immediately after an application is sent to Lever successfully. Same `$event->application` and `$event->isSpam` properties available.

## Support

Please submit [an issue](https://github.com/workingconcept/lever-craft-plugin/issues) or [a pull request](https://github.com/workingconcept/lever-craft-plugin/pulls) if anything needs attention. We'll do our best to respond promptly.

---

This plugin is brought to you by [Working Concept](https://workingconcept.com), which has no affiliation with Lever.
