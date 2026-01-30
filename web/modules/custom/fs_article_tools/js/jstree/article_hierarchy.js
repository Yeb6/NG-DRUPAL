(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.fsArticleTree = {
    attach: function (context, settings) {
      const $treeContainer = $('#article-jstree', context).not('.fsArticleTree');
      if (!$treeContainer.length) return;
      $treeContainer.addClass('fsArticleTree');

      const treeData = drupalSettings.fs_article_tools?.treeData || [];
      console.log(treeData)
      if (!Array.isArray(treeData) || treeData.length === 0) {
        $treeContainer.text('No articles available.');
        return;
      }

      const nodes = treeData.map(item => ({
        id: item.id,
        parent: item.parent || '#',
        text: item.text,
        icon: item.type === 'chapter' ? 'jstree-folder' : 'jstree-file'
      }));

      const tree = $treeContainer.jstree({
        core: {
          data: nodes,
          multiple: false,
          check_callback: true,
          themes: { stripes: true, icons: true }
        },
        plugins: ['wholerow']
      });

      tree.on('changed.jstree', function (e, data) {
        const selectedId = data.selected[0] || '';
        $('.js-article-tree-value').val(selectedId);

        // Highlight selected node
        $treeContainer.find('.highlight-node').removeClass('highlight-node');
        if (selectedId) {
          $(data.instance.get_node(selectedId, true)).addClass('highlight-node');
        }
      });

      tree.on('ready.jstree', function () {
        const defaultVal = $('.js-article-tree-value').val();
        if (!defaultVal) return;

        const treeInstance = $treeContainer.jstree(true);

        // Deselect all
        treeInstance.deselect_all();

        // Select the current node
        treeInstance.select_node(defaultVal);

        // Expand all ancestors
        const node = treeInstance.get_node(defaultVal);
        if (node && node.parents && node.parents.length) {
          node.parents
            .filter(parentId => parentId !== '#') // ignore root
            .reverse() // open from top-level down
            .forEach(parentId => {
              treeInstance.open_node(parentId, function () {
                // optional callback
              }, true); // animated
            });
        }

        // Open the node itself with animation
        treeInstance.open_node(defaultVal, function () {
          // Highlight after animation
          $(treeInstance.get_node(defaultVal, true)).addClass('highlight-node');

          // Scroll into view
          const $nodeEl = $(treeInstance.get_node(defaultVal, true));
          if ($nodeEl.length) {
            $treeContainer.animate({
              scrollTop: $nodeEl.position().top + $treeContainer.scrollTop() - 50
            }, 500);
          }
        }, true);
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
