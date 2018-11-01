# Contributing to WP Starter
First, let us say: **thank you** to be here! :+1:

---



## Did you find a bug?

- **Do not open up a GitHub issue if the bug is a security vulnerability**, and instead contact [Giuseppe](https://github.com/gmazzap) directly via email.
- Ensure the bug was not already reported by searching on GitHub under [issues](https://github.com/wecodemore/wpstarter/issues).
- If you're unable to find an open issue addressing the problem, [open a new one](https://github.com/wecodemore/wpstarter/issues/new?template=bug_report.md). Be sure to include a title and clear description, as much relevant information as possible. Code samples and / or executable test cases demonstrating the bug are very welcome.
- Use the bug report template to create the issue, providing all the information listed there.
- If you'd like to write a fix yourself, **please wait first for some feedback on the issue**. Then follow guidelines below to write the code and submit a PR.



## Would you like to add a new feature or change an existing one?

- [Open an issue](https://github.com/wecodemore/wpstarter/issues/new?template=feature_request.md) clearly explaining what feature you want to add or change and why. 
- Use the feature request template to create the issue, providing all the information listed there.
- If you offer to implement the change yourself, there will be more chances that the addition / change will land to WP Starter. But **please don't start writing code before you get a positive feedback**.



---



## Guidelines

### Code Style

We use [Inpsyde PHP coding standars](https://github.com/inpsyde/php-coding-standards).

By cloning the repository and running:

```shell
./vendor/bin/phpcs
```

it is possible to check code is compliant via [PHPCS](https://github.com/squizlabs/PHP_CodeSniffer).

Be sure to install Composer dependencies first.

### Git

Before write any code:

- Ensure an issue exist describing the bug, the wanted change or the new feature. If not, [open one](https://github.com/wecodemore/wpstarter/issues/new/choose).

- Create **a new branch** named after the target issue plus a one-or-two words description of the topic:


```shell
git checkout -b issue-123-something-important
```

#### Commits

Please make one commit _per change_. If you are afraid that you might end up with too many commits, you can [squash commits](https://github.com/servo/servo/wiki/Beginner's-guide-to-rebasing-and-squashing).

Please, always write clear messages for your commits. A good message is succinct, but still clearly explains *why* the change has been made, more than *how*.

The whole commit message is made of 2 or 3 parts:

1. A summary, *mandatory*, 60 characters or less, used **only** to explain the changes. It should read well when read after the sentence *"If merged this commit will..."*
2. A more detailed description, *optional*, used only if the 60 characters of the summary are not enough to explain changes well.
3. A reference to the relevant issue(s), *mandatory*.

The different parts can be added to the commit by using the `-m` option multiple times. For example:

```bash
git commit \
 -m"Improve output messages of wp-config step" \
 -m"Old message was not making clear if the step failed or not" \
 -m"See #123"
```

#### Pull Requests

1. Always **file PRs against the `dev` branch**. Nothing goes to `master` without going to `dev` first. In case you did not do that, please [update your pull request](https://help.github.com/articles/changing-the-base-branch-of-a-pull-request/).
1. If possible, include tests. We can always use more test coverage.
1. Use the text on the PR with a description of the change made. There's no need to duplicate information from the issue, but please remember to link the issue in the PR text.
