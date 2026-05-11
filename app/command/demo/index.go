package demo

import (
	"easy-local/core/application"
	"fmt"
)

func init() {
	application.Instance().
		// 注册类处理
		RegCmdClass(new(Index))
}

type Index struct {
	application.BaseHandle
}

// @command `go run main.go demo/index`
func (i *Index) Handle() {
	fmt.Println("默认处理")
}

// @command `go run main.go demo/index/test`
func (i *Index) Test() {
	fmt.Println("测试")
}
