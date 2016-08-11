# Contributing to _WPStarter_
**First**, let us say: Thank you for contributing! :+1:
The outlined steps in this document are just guidelines, not rules, 
use your best judgment and feel free to propose changes to this document 
in [a new pull request][github-pr-link].

---

## What should I know before getting started?
In general, it's the following rule:

> Try to play nice with later developers or people using this package.

### Submitting changes
1. Please open [an issue][github-issues-link] 
   to discuss your changes before filing a pull request.
1. Always add changes to **a new branch** named 

        git checkout -b issue-<issue number>-<topic>
        # Example
        # issue-54-feature-title

    An exception are extremely minor changes. Those can go directly to `dev`.  

1. Always **file PRs against the `dev` branch**. Nothing goes to `master` without 
   going to `dev` first. In case you did not do that, please just update/rebase 
   your pull request.
1. If you include tests, we will love you in all eternity. 
   **Hint:** We can always use more test coverage.
1. When you send a pull request, please try to explain your changes. A list of 
   keywords is enough.

We will label your issue or PR accordingly so that you can filter the list of 
issues with the least effort possible. We also assign issues to milestones and 
release minor or major versions when the list of issues exceeds a certain 
threshold.

### Commit changes
File one commit _per change_. If you are afraid that you might end up with too 
many commits, you can [squash commits][so-git-squash].

Please, always write a clear log message for your commits. It's always hard 
to find changes that introduced bugs. Good messages makes it easier to trace 
things back to their origin.

We really like the 
[Angular commit message format][angular-contrib-docs-link] 
a lot. Here's an example:

```
<type>(<scope>) <subject>, see #<issue number>
    <BLANK LINE>
<body>
```

Please make sure that you **always include the issue number** in a commit message. 
Else GitHub issues do not add the commits to the issues. Example: `see #23`

 * Avoid a period/dot/`.` at the end of the commit message
 * Use the imperative, present tense "change", not "changed" nor "changes"
 * Limit the subject line to 50 characters
 * One change per `<body>` line - think of it as a "list of changes", things
  that end up in a changelog
 

Specific example:

```
docs(test) Explain how to set up tests, see #51

Add README.md 'tests' section
Explain general tools to use
Explain local setup
Explain CI setup
```

In most cases the `<type>` should be one of the following:

 * `feat` (new feature)
 * `fix` (bug fix)
 * `docs` (changes to documentation, README, …)
 * `style` (css, missing semi colons, etc; no code change)
 * `refactor` (restructuring docs, refactoring production code)
 * `test` (adding missing tests, refactoring tests; no production code change)

The `<scope>` will be the _class or component_ your changes are for. 
You can think of it as _topic_ as well.

[github-pr-link]: https://github.com/wecodemore/wpstarter/compare
[github-issues-link]: https://github.com/wecodemore/wpstarter/issues/new
[so-git-squash]: http://stackoverflow.com/a/5201642/376483
[angular-contrib-docs-link]: https://github.com/angular/angular.js/blob/5d695e5566212d93da0fc1281d5d39ffee0039a3/CONTRIBUTING.md#commit-message-format